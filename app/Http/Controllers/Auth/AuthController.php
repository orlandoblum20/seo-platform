<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Login - Step 1: Validate credentials
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return response()->json([
                'message' => 'Неверные учётные данные',
            ], 401);
        }

        // Check if account is locked
        if ($user->isLocked()) {
            LoginHistory::record($user->id, LoginHistory::STATUS_BLOCKED, 'Account locked');
            
            $minutesLeft = now()->diffInMinutes($user->locked_until);
            
            return response()->json([
                'message' => "Аккаунт заблокирован. Попробуйте через {$minutesLeft} мин.",
                'locked_until' => $user->locked_until,
            ], 423);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            $user->incrementFailedAttempts();
            LoginHistory::record($user->id, LoginHistory::STATUS_FAILED, 'Invalid password');
            
            $attemptsLeft = 5 - $user->failed_login_attempts;
            
            return response()->json([
                'message' => "Неверный пароль. Осталось попыток: {$attemptsLeft}",
                'attempts_left' => $attemptsLeft,
            ], 401);
        }

        // Check if 2FA is enabled
        if ($user->hasTwoFactorEnabled()) {
            // Create temporary token for 2FA verification
            $tempToken = $user->createToken('2fa-pending', ['2fa-pending'])->plainTextToken;
            
            LoginHistory::record($user->id, LoginHistory::STATUS_2FA_PENDING);
            
            return response()->json([
                'message' => 'Требуется подтверждение 2FA',
                'requires_2fa' => true,
                'temp_token' => $tempToken,
            ]);
        }

        // No 2FA - complete login
        return $this->completeLogin($user);
    }

    /**
     * Login - Step 2: Verify 2FA code
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Verify the 2FA code
        $valid = $this->google2fa->verifyKey(
            $user->google2fa_secret,
            $request->code
        );

        if (!$valid) {
            LoginHistory::record($user->id, LoginHistory::STATUS_2FA_FAILED, 'Invalid 2FA code');
            
            return response()->json([
                'message' => 'Неверный код подтверждения',
            ], 401);
        }

        // Delete the temporary token
        $user->currentAccessToken()->delete();

        // Complete login
        return $this->completeLogin($user);
    }

    /**
     * Complete login process
     */
    private function completeLogin(User $user): JsonResponse
    {
        // Reset failed attempts
        $user->resetFailedAttempts();

        // Create full access token
        $token = $user->createToken('auth-token', ['*'])->plainTextToken;

        // Record successful login
        LoginHistory::record($user->id, LoginHistory::STATUS_SUCCESS);

        return response()->json([
            'message' => 'Успешная авторизация',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_2fa' => $user->hasTwoFactorEnabled(),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Вы вышли из системы',
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Вы вышли со всех устройств',
        ]);
    }

    /**
     * Get current user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_2fa' => $user->hasTwoFactorEnabled(),
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
            ],
        ]);
    }

    /**
     * Setup 2FA - Generate secret
     */
    public function setup2fa(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => '2FA уже включена',
            ], 400);
        }

        $secret = $this->google2fa->generateSecretKey();
        
        // Store secret temporarily (not enabled yet)
        $user->update(['google2fa_secret' => $secret]);

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    /**
     * Enable 2FA - Verify and activate
     */
    public function enable2fa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->google2fa_secret) {
            return response()->json([
                'message' => 'Сначала запросите настройку 2FA',
            ], 400);
        }

        // Verify the code
        $valid = $this->google2fa->verifyKey(
            $user->google2fa_secret,
            $request->code
        );

        if (!$valid) {
            return response()->json([
                'message' => 'Неверный код. Попробуйте ещё раз.',
            ], 400);
        }

        // Enable 2FA
        $user->update(['google2fa_enabled' => true]);

        return response()->json([
            'message' => '2FA успешно включена',
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable2fa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Неверный пароль',
            ], 401);
        }

        // Verify 2FA code
        $valid = $this->google2fa->verifyKey(
            $user->google2fa_secret,
            $request->code
        );

        if (!$valid) {
            return response()->json([
                'message' => 'Неверный код 2FA',
            ], 400);
        }

        // Disable 2FA
        $user->update([
            'google2fa_enabled' => false,
            'google2fa_secret' => null,
        ]);

        return response()->json([
            'message' => '2FA отключена',
        ]);
    }

    /**
     * Get login history
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $history = $request->user()
            ->loginHistory()
            ->latest('logged_in_at')
            ->take(20)
            ->get();

        return response()->json([
            'history' => $history,
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Неверный текущий пароль',
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Logout from all other devices
        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'message' => 'Пароль успешно изменён',
        ]);
    }
}

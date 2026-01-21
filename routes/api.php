<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\DnsAccountController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\BacklinkController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-2fa', [AuthController::class, 'verify2fa'])->middleware('auth:sanctum');
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::get('login-history', [AuthController::class, 'loginHistory']);
        
        // 2FA management
        Route::post('2fa/setup', [AuthController::class, 'setup2fa']);
        Route::post('2fa/enable', [AuthController::class, 'enable2fa']);
        Route::post('2fa/disable', [AuthController::class, 'disable2fa']);
    });

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // DNS Accounts
    Route::apiResource('dns-accounts', DnsAccountController::class);
    Route::post('dns-accounts/{dns_account}/verify', [DnsAccountController::class, 'verify']);
    Route::post('dns-accounts/{dns_account}/sync', [DnsAccountController::class, 'sync']);

    // Servers
    Route::apiResource('servers', ServerController::class);
    Route::post('servers/{server}/health-check', [ServerController::class, 'healthCheck']);
    Route::post('servers/{server}/set-primary', [ServerController::class, 'setPrimary']);
    Route::get('servers-stats', [ServerController::class, 'stats']);

    // Domains
    // Domains - custom routes first (before apiResource)
    Route::get('domains/filter-options', [DomainController::class, 'filterOptions']);
    Route::post('domains/import', [DomainController::class, 'import']);
    Route::post('domains/bulk-delete', [DomainController::class, 'bulkDelete']);
    Route::post('domains/bulk-recheck-status', [DomainController::class, 'bulkRecheckStatus']);
    Route::post('domains/bulk-setup-ssl', [DomainController::class, 'bulkSetupSsl']);
    Route::post('domains/update-ip', [DomainController::class, 'updateIp']);
    Route::post('domains/move-dns-account', [DomainController::class, 'moveToDnsAccount']);
    Route::post('domains/export', [DomainController::class, 'export']);
    Route::apiResource('domains', DomainController::class)->except(['store']);
    Route::get('domains/{domain}/check-ssl', [DomainController::class, 'checkSsl']);
    Route::get('domains/{domain}/ssl-details', [DomainController::class, 'getSslDetails']);
    Route::post('domains/{domain}/setup-ssl', [DomainController::class, 'setupSsl']);
    Route::post('domains/{domain}/recheck-status', [DomainController::class, 'recheckStatus']);
    Route::get('domains-stats', [DomainController::class, 'stats']);

    // Templates
    Route::apiResource('templates', TemplateController::class);
    Route::post('templates/{template}/duplicate', [TemplateController::class, 'duplicate']);
    Route::post('templates/reorder', [TemplateController::class, 'reorder']);
    Route::get('templates-types', [TemplateController::class, 'types']);

    // Sites
    Route::apiResource('sites', SiteController::class);
    Route::post('sites/bulk-create', [SiteController::class, 'bulkCreate']);
    Route::post('sites/{site}/generate', [SiteController::class, 'generate']);
    Route::post('sites/{site}/regenerate-section', [SiteController::class, 'regenerateSection']);
    Route::post('sites/{site}/publish', [SiteController::class, 'publish']);
    Route::post('sites/{site}/unpublish', [SiteController::class, 'unpublish']);
    Route::post('sites/bulk-publish', [SiteController::class, 'bulkPublish']);
    Route::post('sites/bulk-unpublish', [SiteController::class, 'bulkUnpublish']);
    Route::get('sites/{site}/preview', [SiteController::class, 'preview']);
    Route::get('sites-stats', [SiteController::class, 'stats']);

    // Backlinks
    Route::apiResource('backlinks', BacklinkController::class);
    Route::post('backlinks/{backlink}/add-anchor', [BacklinkController::class, 'addAnchor']);
    Route::post('backlinks/assign-to-sites', [BacklinkController::class, 'assignToSites']);
    Route::post('backlinks/remove-from-sites', [BacklinkController::class, 'removeFromSites']);
    Route::post('backlinks/randomize-anchors', [BacklinkController::class, 'randomizeAnchors']);
    Route::get('backlinks-groups', [BacklinkController::class, 'groups']);
    Route::get('backlinks-stats', [BacklinkController::class, 'stats']);

    // Posts (Autoposting)
    Route::apiResource('posts', PostController::class);
    Route::post('posts/{post}/publish', [PostController::class, 'publish']);
    Route::post('posts/bulk-generate', [PostController::class, 'bulkGenerate']);
    Route::get('posts-stats', [PostController::class, 'stats']);
    Route::get('posts-scheduled', [PostController::class, 'scheduledQueue']);
    
    // Autopost Settings
    Route::get('sites/{site}/autopost-settings', [PostController::class, 'getAutopostSettings']);
    Route::put('sites/{site}/autopost-settings', [PostController::class, 'updateAutopostSettings']);
    Route::post('autopost/bulk-enable', [PostController::class, 'bulkEnableAutopost']);
    Route::post('autopost/bulk-disable', [PostController::class, 'bulkDisableAutopost']);

    // Settings
    Route::prefix('settings')->group(function () {
        // Global settings
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        
        // AI Settings
        Route::get('ai/models', [SettingsController::class, 'getModels']);
        Route::get('ai', [SettingsController::class, 'getAiSettings']);
        Route::post('ai', [SettingsController::class, 'createAiSetting']);
        Route::put('ai/{ai_setting}', [SettingsController::class, 'updateAiSetting']);
        Route::delete('ai/{ai_setting}', [SettingsController::class, 'deleteAiSetting']);
        Route::post('ai/{ai_setting}/test', [SettingsController::class, 'testAiSetting']);
        Route::post('ai/{ai_setting}/set-default', [SettingsController::class, 'setDefaultAi']);
        
        // Keitaro Settings
        Route::get('keitaro', [SettingsController::class, 'getKeitaroSettings']);
        Route::put('keitaro', [SettingsController::class, 'updateKeitaroSettings']);
        
        // Analytics Codes
        Route::get('analytics', [SettingsController::class, 'getAnalyticsSettings']);
        Route::put('analytics', [SettingsController::class, 'updateAnalyticsSettings']);
        
        // Group settings (must be LAST due to wildcard)
        Route::get('group/{group}', [SettingsController::class, 'getGroup']);
    });

    // Activity Log
    Route::get('activity-log', [DashboardController::class, 'activityLog']);

    // Jobs Queue Status
    Route::get('queue-status', [DashboardController::class, 'queueStatus']);
});

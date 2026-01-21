<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create 
                            {--email= : Admin email address}
                            {--password= : Admin password}
                            {--name= : Admin name}';

    protected $description = 'Create a new admin user';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Enter admin email');
        $password = $this->option('password') ?? $this->secret('Enter admin password');
        $name = $this->option('name') ?? $this->ask('Enter admin name', 'Administrator');

        // Validate
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ], [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'name' => 'required|min:2',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Admin user created successfully!");
        $this->table(['Field', 'Value'], [
            ['ID', $user->id],
            ['Name', $user->name],
            ['Email', $user->email],
        ]);

        return self::SUCCESS;
    }
}

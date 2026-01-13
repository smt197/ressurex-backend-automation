<?php

namespace App\Console\Commands;

use App\Services\RegisterUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new user';

    protected $registerUserService;

    /**
     * Create a new command instance.
     */
    public function __construct(RegisterUserService $registerUserService)
    {
        parent::__construct();
        $this->registerUserService = $registerUserService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $password = Hash::make('P@sser12');
            $uuid = (string) Str::uuid();
            $remember_token = Str::random(60);
            $email_verified_at = now();
            $userData = [
                'uuid' => $uuid,
                'first_name' => 'Scheduled',
                'last_name' => 'User',
                'email' => 'scheduled.'.time().'@gmail.com',
                'password' => $password,
                'confirmed' => true,
                'remember_token' => $remember_token,
                'email_verified_at' => $email_verified_at,
            ];

            $user = $this->registerUserService->createUser($userData);

            Log::info('User created successfully via schedule: '.$user->email);
            $this->info('User created successfully: '.$user->email);
        } catch (\Exception $e) {
            Log::error('Error creating user via schedule: '.$e->getMessage());
            $this->error('Error creating user: '.$e->getMessage());
        }
    }
}

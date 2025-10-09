<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email functionality by sending verification and password reset emails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Testing email functionality for: {$email}");
        
        // Test 1: Send verification email
        $this->info("1. Testing email verification...");
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->sendEmailVerificationNotification();
                $this->info("✅ Verification email sent successfully!");
            } else {
                $this->warn("⚠️  User not found. Creating test user...");
                $user = User::create([
                    'firstname' => 'Test',
                    'lastname' => 'User',
                    'username' => 'testuser' . rand(1000, 9999),
                    'email' => $email,
                    'password' => bcrypt('password'),
                    'gender' => 'male',
                    'bdate' => '1990-01-01',
                    'address' => 'Test Address',
                    'role' => 'user',
                    'plan' => 'free',
                    'token_balance' => 0,
                ]);
                $user->sendEmailVerificationNotification();
                $this->info("✅ Test user created and verification email sent!");
            }
        } catch (\Exception $e) {
            $this->error("❌ Verification email failed: " . $e->getMessage());
        }
        
        // Test 2: Send password reset email
        $this->info("2. Testing password reset email...");
        try {
            if ($user) {
                $user->sendPasswordResetNotification('test-token-123');
                $this->info("✅ Password reset email sent successfully!");
            }
        } catch (\Exception $e) {
            $this->error("❌ Password reset email failed: " . $e->getMessage());
        }
        
        // Test 3: Send simple test email
        $this->info("3. Testing simple email...");
        try {
            Mail::raw('This is a test email from SkillsXchange application.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email from SkillsXchange');
            });
            $this->info("✅ Simple test email sent successfully!");
        } catch (\Exception $e) {
            $this->error("❌ Simple test email failed: " . $e->getMessage());
        }
        
        $this->info("Email testing completed! Check your inbox at {$email}");
        
        return Command::SUCCESS;
    }
}

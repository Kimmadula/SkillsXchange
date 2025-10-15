<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearOldSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:clear-old {--force : Force clear without confirmation} {--domain-migration : Clear sessions for domain migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old sessions from domain migration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will clear all old sessions. Continue?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        if ($this->option('domain-migration')) {
            $this->info('Clearing sessions for domain migration...');
        } else {
            $this->info('Clearing old sessions from domain migration...');
        }

        try {
            // Clear session files
            $this->clearSessionFiles();
            
            // Clear cache
            $this->clearCache();
            
            // Clear old cookies (if possible)
            $this->clearOldCookies();
            
            // Additional domain migration steps
            if ($this->option('domain-migration')) {
                $this->clearDomainMigrationData();
            }
            
            $this->info('✅ Old sessions cleared successfully!');
            $this->info('Users will need to log in again on the new domain.');
            
        } catch (\Exception $e) {
            $this->error('Error clearing sessions: ' . $e->getMessage());
            Log::error('ClearOldSessions error: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear session files
     */
    private function clearSessionFiles()
    {
        $sessionPath = storage_path('framework/sessions');
        
        if (File::exists($sessionPath)) {
            $files = File::files($sessionPath);
            $count = 0;
            
            foreach ($files as $file) {
                if (File::delete($file)) {
                    $count++;
                }
            }
            
            $this->info("Cleared {$count} session files");
        }
    }
    
    /**
     * Clear cache
     */
    private function clearCache()
    {
        Cache::flush();
        $this->info('Cache cleared');
    }
    
    /**
     * Clear old cookies (informational)
     */
    private function clearOldCookies()
    {
        $this->info('Note: Old cookies will be cleared when users visit the new domain');
        $this->info('The HandleDomainChange middleware will handle this automatically');
    }
    
    /**
     * Clear domain migration specific data
     */
    private function clearDomainMigrationData()
    {
        // Clear any domain-specific cache
        Cache::forget('domain_migration_completed');
        Cache::forget('old_domain_sessions');
        
        // Clear any old domain references
        $this->info('Cleared domain migration cache');
    }
}
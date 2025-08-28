<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupOldSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup {--days=7 : Number of days to keep sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old session files to improve performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days);
        $sessionsPath = storage_path('framework/sessions');
        $deletedCount = 0;

        if (!File::exists($sessionsPath)) {
            $this->info('Sessions directory does not exist.');
            return 0;
        }

        $files = File::files($sessionsPath);
        
        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff->timestamp) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        $this->info("Cleaned up {$deletedCount} old session files older than {$days} days.");
        
        return 0;
    }
} 
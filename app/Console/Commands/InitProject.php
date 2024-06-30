<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InitProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Project initialization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Clear and reset the database
        Artisan::call('db:wipe'); // Wipe the database
        Artisan::call('migrate'); // Run all migrations
        Artisan::call('db:seed'); // Seed the database with initial data

        // Install passport
        Artisan::call('passport:install');

        // Clear and cache the configuration
        Artisan::call('config:cache'); // Cache the configuration
        Artisan::call('config:clear'); // Clear the configuration cache
        Artisan::call('route:cache'); // Clear route cache

        $this->info('Project initialized successfully.');
    }
}

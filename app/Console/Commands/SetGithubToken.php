<?php

namespace App\Console\Commands;

use App\Models\GithubSettingsModel;
use Illuminate\Console\Command;

class SetGithubToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-github-token {token : The GitHub Personal Access Token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the GitHub token in the database settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = $this->argument('token');

        if (empty($token)) {
            $this->error('Token cannot be empty.');
            return 1;
        }

        try {
            // Get or create the settings entry
            $settings = GithubSettingsModel::getOrCreateGithubToken();
            
            // Update the payload (value) using the mutator
            $settings->github_token = $token;
            $settings->save();

            $this->info('GitHub token has been successfully stored in the database.');
            $this->info('This token will now take precedence over environment variables.');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to save token: ' . $e->getMessage());
            return 1;
        }
    }
}

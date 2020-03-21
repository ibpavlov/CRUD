<?php

namespace Backpack\CRUD\app\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Install extends Command
{
    use Traits\PrettyCommandOutput;

    protected $progressBar;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backpack:install
                                {--timeout=300} : How many seconds to allow each process to run.
                                {--debug} : Show process output or not. Useful for debugging.';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Backpack requirements on dev, publish files and create uploads directory.';

    /**
     * Execute the console command.
     *
     * @return mixed Command-line output
     */
    public function handle()
    {
        $this->progressBar = $this->output->createProgressBar(6);
        $this->progressBar->minSecondsBetweenRedraws(0);
        $this->progressBar->maxSecondsBetweenRedraws(120);
        $this->progressBar->setRedrawFrequency(1);

        $this->progressBar->start();

        $this->info(' Backpack installation started. Please wait...');
        $this->progressBar->advance();

        $this->line(' Publishing configs, langs, views, js and css files');
        $this->executeArtisanProcess('vendor:publish', [
            '--provider' => 'Backpack\CRUD\BackpackServiceProvider',
            '--tag' => 'minimum',
        ]);

        $this->line(' Publishing config for notifications - prologue/alerts');
        $this->executeArtisanProcess('vendor:publish', [
            '--provider' => 'Prologue\Alerts\AlertsServiceProvider',
        ]);

        $this->line(" Generating users table (using Laravel's default migrations)");
        $this->executeArtisanProcess('migrate');

        $this->line(" Creating App\Models\BackpackUser.php");
        $this->executeArtisanProcess('backpack:publish-user-model');

        $this->line(" Creating App\Http\Middleware\CheckIfAdmin.php");
        $this->executeArtisanProcess('backpack:publish-middleware');

        $this->progressBar->finish();
        $this->info(' Backpack installation finished.');
    }
}

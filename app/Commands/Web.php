<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\Concerns\RequiresApplication;
use App\Concerns\RequiresRemoteGitRepo;
use App\Git;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class Web extends BaseCommand
{
    use HasAClient;
    use RequiresApplication;
    use RequiresRemoteGitRepo;

    protected $signature = 'web
                            {application? : The application ID or name}';

    protected $description = 'Open the application in the Cloud dashboard';

    public function handle()
    {
        intro('Opening Cloud Dashboard');

        $this->ensureClient();
        $this->ensureRemoteGitRepo();

        $repository = app(Git::class)->remoteRepo();

        $applications = spin(
            fn () => $this->client->applications()->withDefaultIncludes()->list(),
            'Checking for existing application...',
        );

        $existingApps = $applications->collect()->filter(
            fn ($app) => $app->repositoryFullName === $repository,
        );

        if ($existingApps->isEmpty()) {
            warning('No existing Cloud application found for this repository.');

            $shouldShip = confirm('Do you want to ship this application to Laravel Cloud?');

            if ($shouldShip) {
                Artisan::call('ship', [], $this->output);

                return;
            }

            error('Cancelled.');

            exit(1);
        }

        $app = $this->getCloudApplication($existingApps);

        $url = $app->url();

        Process::run('open '.$url);

        outro($url);
    }
}

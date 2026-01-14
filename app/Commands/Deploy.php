<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\ConfigRepository;
use App\Dto\Deployment;
use App\Git;
use App\Prompts\DynamicSpinner;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Support\Sleep;
use Laravel\Prompts\Concerns\Colors;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class Deploy extends Command
{
    use Colors;
    use HasAClient;

    protected $signature = 'deploy {application? : The ID of the application to deploy} {environment? : The name of the environment to deploy}';

    protected $description = 'Deploy the application to Laravel Cloud';

    public function handle(ConfigRepository $config, Git $git)
    {
        intro('Deploying application to Laravel Cloud');

        $this->ensureClient();
        $this->ensureGitHubRepo($git);
        $this->ensureCloudApplication($git);
    }

    protected function ensureCloudApplication(Git $git): void
    {
        $repository = $git->remoteRepo();

        $applications = spin(
            fn () => $this->client->listApplications(),
            'Checking for existing application...'
        );

        $existingApps = collect($applications->data ?? [])->filter(
            fn ($app) => $app->repositoryFullName === $repository
        );

        if ($existingApps->isEmpty()) {
            warning('No existing Cloud application found for this repository.');

            exit(1);
        }

        if ($this->argument('application')) {
            $app = $existingApps->firstWhere('id', $this->argument('application'));
            answered(label: 'Application', answer: "{$app->name}");
        } elseif ($existingApps->count() === 1) {
            $app = $existingApps->first();
            answered(label: 'Application', answer: "{$app->name}");
        } else {
            $selectedApp = select(
                label: 'Select an application',
                options: $existingApps->mapWithKeys(fn ($app) => [$app->id => $app->name]),
            );

            $app = $existingApps->firstWhere('id', $selectedApp);
        }

        $environments = spin(
            fn () => $this->client->listEnvironments($app->id),
            'Checking for existing environments...'
        );

        if ($this->argument('environment')) {
            $environment = collect($environments->data)->firstWhere('name', $this->argument('environment'));
            answered(label: 'Environment', answer: "{$environment->name}");
        } elseif (count($environments->data) === 1) {
            $environment = $environments->data[0];
            answered(label: 'Environment', answer: "{$environment->name}");
        } else {
            $selection = select(
                label: 'Select an environment',
                options: collect($environments->data)->mapWithKeys(fn ($env) => [$env->id => $env->name]),
            );
            $environment = collect($environments->data)->firstWhere('id', $selection);
        }

        $deployment = $this->client->initiateDeployment($environment->id);

        (new DynamicSpinner($this->getDeploymentMessage($deployment)))->spin(function (callable $updateMessage) use ($deployment) {
            $checkApi = true;
            $total = 0;
            $checkInterval = 3;
            $updateInterval = 900;

            do {
                if ($checkApi) {
                    $deploymentStatus = $this->client->getDeployment($deployment->id);
                }

                $updateMessage($this->getDeploymentMessage($deploymentStatus));
                Sleep::for(CarbonInterval::milliseconds($updateInterval));
                $total++;
                $checkApi = $total % $checkInterval === 0;
            } while (! $deploymentStatus->isCompleted());
        });

        $deployment = $this->client->getDeployment($deployment->id);

        success('Deployment completed in '.$deployment->totalTime()->format('%I:%S'));
    }

    protected function getDeploymentMessage(Deployment $deployment): string
    {
        $timeElapsed = $deployment->startedAt?->diffInSeconds(CarbonImmutable::now());

        return sprintf(
            $this->dim('%s:%s').' <info>%s</info>',
            str_pad(floor($timeElapsed / 60), 2, '0', STR_PAD_LEFT),
            str_pad($timeElapsed % 60, 2, '0', STR_PAD_LEFT),
            $deployment->status->label(),
        );
    }

    protected function ensureGitHubRepo(Git $git): void
    {
        if ($git->hasGitHubRemote()) {
            return;
        }

        throw new Exception('GitHub repository not found');
    }
}

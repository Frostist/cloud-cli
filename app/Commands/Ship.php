<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\ConfigRepository;
use App\Dto\Application;
use App\Dto\Deployment;
use App\Enums\CloudRegion;
use App\Enums\DeploymentStatus;
use App\Git;
use App\Prompts\WeMustShip;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Sleep;
use Laravel\Prompts\Concerns\Colors;
use LaravelZero\Framework\Commands\Command;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class Ship extends Command
{
    use Colors;
    use HasAClient;

    protected $signature = 'ship';

    protected $description = 'Ship the application to Laravel Cloud';

    public function handle(ConfigRepository $config, Git $git)
    {
        $this->newLine();
        (new WeMustShip)->animate();
        $this->newLine();

        intro('Shipping application to Laravel Cloud');

        $this->ensureClient();
        $this->ensureGitHubRepo($git);
        $this->createCloudApplication($git);
    }

    protected function createCloudApplication(Git $git): void
    {
        $repository = $git->remoteRepo();

        $applications = spin(
            fn () => $this->client->listApplications(),
            'Checking for existing application...'
        );

        $existingApps = collect($applications->data)->filter(
            fn (Application $app) => $app->repositoryFullName === $repository
        );

        if ($existingApps->isNotEmpty()) {
            info('Found '.$existingApps->count().' existing applications for this repository.');

            $options = $existingApps->mapWithKeys(fn (Application $app) => [$app->id => 'Deploy '.$app->name]);
            $options->prepend('Create new application', 'new');

            $selection = select(
                label: 'Select an application',
                options: $options,
            );

            if ($selection !== 'new') {
                Artisan::call('deploy', [
                    'application' => $selection,
                ]);

                return;
            }
        }

        $appName = text(
            label: 'Application name',
            default: $git->currentDirectoryName(),
            required: true,
        );

        $mostUsedRegion = collect($applications->data)->pluck('region')->countBy()->sortDesc()->keys()->first();
        $defaultRegion = CloudRegion::tryFrom($mostUsedRegion ?? '')?->value ?? CloudRegion::US_EAST_2->value;

        $region = select(
            label: 'Application region',
            options: collect(CloudRegion::cases())->mapWithKeys(fn (CloudRegion $region) => [$region->value => $region->label()]),
            default: $defaultRegion,
        );

        $application = spin(
            fn () => $this->client->createApplication($repository, $appName, $region),
            'Creating application...'
        );

        if ($application) {
            success('Application created!');
        } else {
            error('Failed to create application: '.($application['message'] ?? 'Unknown error'));

            exit(1);
        }

        $application = $this->client->getApplication($application->id);

        $envPath = getcwd().'/.env';

        if (file_exists($envPath)) {
            try {
                $variables = Dotenv::parse(file_get_contents($envPath));
            } catch (Throwable $e) {
                //
            }

            $diff = array_diff(array_keys($variables), config('env.laravel'));

            if (count($diff) > 0) {
                $diffVariables = collect($diff)->mapWithKeys(fn ($key) => [
                    $key => $key.$this->dim(str($variables[$key])->limit(5)->prepend(' (')->append(')')),
                ]);
                $varsToAdd = multiselect('Add local environment variables to Cloud environment?', options: $diffVariables);

                if (count($varsToAdd) > 0) {
                    $varsToAdd = collect($varsToAdd)->mapWithKeys(fn ($key) => [$key => $variables[$key]]);

                    spin(
                        function () use ($application, $varsToAdd) {
                            while (count($application->environmentIds) === 0) {
                                $application = $this->client->getApplication($application->id);
                                Sleep::for(CarbonInterval::seconds(1));
                            }

                            $this->client->replaceEnvironmentVariables($application->environmentIds[0], $varsToAdd->toArray());
                        },
                        'Adding selected variables to Cloud environment...'
                    );
                }
            }
        }

        info(sprintf('https://cloud.laravel.com/%s/%s', $application->organizationId, $application->slug));
    }

    protected function getDeploymentMessage(Deployment $deployment): string
    {
        $statusMessage = match ($deployment->status) {
            DeploymentStatus::BUILD_PENDING => 'Building...',
            DeploymentStatus::BUILD_CREATED => 'Build created...',
            DeploymentStatus::BUILD_QUEUED => 'Build queued...',
            DeploymentStatus::BUILD_RUNNING => 'Build running...',
            DeploymentStatus::BUILD_SUCCEEDED => 'Build succeeded...',
            DeploymentStatus::BUILD_FAILED => 'Build failed...',
            DeploymentStatus::DEPLOYMENT_PENDING => 'Deploying...',
            DeploymentStatus::DEPLOYMENT_CREATED => 'Deployment created...',
            DeploymentStatus::DEPLOYMENT_QUEUED => 'Deployment queued...',
            DeploymentStatus::DEPLOYMENT_RUNNING => 'Deployment running...',
            DeploymentStatus::DEPLOYMENT_SUCCEEDED => 'Deployment succeeded...',
            DeploymentStatus::DEPLOYMENT_FAILED => 'Deployment failed...',
            DeploymentStatus::CANCELLED => 'Cancelled...',
            DeploymentStatus::FAILED => 'Failed!',
            DeploymentStatus::PENDING => 'Pending...',
            default => $deployment->status,
        };

        $timeElapsed = $deployment->startedAt?->diffInSeconds(CarbonImmutable::now());

        return sprintf(
            $this->dim('%s:%s').' <info>%s</info>',
            str_pad(floor($timeElapsed / 60), 2, '0', STR_PAD_LEFT),
            str_pad($timeElapsed % 60, 2, '0', STR_PAD_LEFT),
            $statusMessage,
        );
    }

    protected function ensureGitHubRepo(Git $git): void
    {
        if ($git->hasGitHubRemote()) {
            return;
        }

        if (! $git->ghInstalled() || ! $git->ghAuthenticated()) {
            warning('This directory is not a Git repository. A Git repository is required to deploy to Laravel Cloud.');

            exit(1);
        }

        if ($git->isRepo()) {
            $createRepo = confirm(
                label: 'No GitHub remote found. Would you like to create a GitHub repository?',
                default: true,
            );

            if (! $createRepo) {
                exit(0);
            }
        } else {
            $createRepo = confirm(
                label: 'This directory is not a Git repository. Would you like to create one?',
                default: true,
            );

            if (! $createRepo) {
                warning('Your codebase must be in a Git repository in order to deploy to Laravel Cloud.');

                exit(0);
            }

            $git->initRepo();
            info('Git repository initialized.');
        }

        $username = $git->getGitHubUsername();
        $orgs = $git->getGitHubOrgs();

        $owners = collect([$username])->merge($orgs)->filter()->mapWithKeys(fn ($org) => [$org => $org]);

        if ($owners->count() === 1) {
            $owner = $owners->first();
            info('Using GitHub account: '.$owner);
        } else {
            $owner = select(
                label: 'Which GitHub account should own this repository?',
                options: $owners,
                default: $owners->first(),
            );
        }

        $repoName = text(
            label: 'Repository name',
            default: $git->currentDirectoryName(),
            required: true,
        );

        $visibility = select(
            label: 'Repository visibility',
            options: [
                'private' => 'Private',
                'public' => 'Public',
            ],
            default: 'private',
        );

        $result = $git->createGitHubRepo($repoName, $owner, $visibility === 'private');

        if (! $result->successful()) {
            error('Failed to create repository: '.$result->errorOutput());

            exit(1);
        }

        info("Repository created: https://github.com/{$owner}/{$repoName}");

        $shouldCommit = confirm(
            label: 'Would you like to add, commit, and push your files?',
            default: true,
        );

        if (! $shouldCommit) {
            return;
        }

        $commitMessage = text(
            label: 'Commit message',
            default: 'first commit',
            required: true,
        );

        $git->addAll();

        $commitResult = $git->commit($commitMessage);

        if (! $commitResult->successful()) {
            error('Failed to commit: '.$commitResult->errorOutput());

            exit(1);
        }

        info('Files committed successfully.');

        $pushResult = $git->push();

        if (! $pushResult->successful()) {
            error('Failed to push: '.$pushResult->errorOutput());

            exit(1);
        }

        info('Pushed to GitHub successfully.');
    }
}

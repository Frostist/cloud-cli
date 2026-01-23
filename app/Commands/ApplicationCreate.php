<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\Concerns\RequiresRemoteGitRepo;
use App\Concerns\Validates;
use App\Dto\ValidationErrors;
use App\Enums\CloudRegion;
use App\Git;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class ApplicationCreate extends BaseCommand
{
    use HasAClient;
    use RequiresRemoteGitRepo;
    use Validates;

    protected $signature = 'application:create
                            {--name= : Application name}
                            {--repository= : Repository (owner/repo format)}
                            {--region= : Application region}
                            {--json : Output as JSON}';

    protected $description = 'Create a new application';

    protected ?string $applicationName = null;

    protected ?string $repository = null;

    protected ?string $region = null;

    protected ?string $defaultRegion = null;

    public function handle()
    {
        $this->ensureClient();

        $this->intro('Creating application');

        $application = $this->loopUntilValid(
            fn (ValidationErrors $errors) => $this->createApplication($errors)
        );

        $this->outputJsonIfWanted($application);

        $this->outro("Application created: {$application->name}");
    }

    protected function createApplication(ValidationErrors $errors)
    {
        $this->breakValidationLoopIfNotInteractive($errors);

        $git = app(Git::class);

        $this->applicationName = $this->resolve('name', $this->applicationName)
            ->fromInput(fn ($currentValue) => text(
                label: 'Application name',
                default: $currentValue,
                required: true,
            ))
            ->errors($errors)
            ->value();

        $this->repository = $this->resolve('repository', $this->repository)
            ->fromInput(fn (?string $value) => text(
                label: 'Repository',
                required: true,
                default: $value ?? ($git->hasGitHubRemote() ? $git->remoteRepo() : null),
            ))
            ->nonInteractively(fn () => $git->hasGitHubRemote() ? $git->remoteRepo() : null)
            ->errors($errors)
            ->value();

        $this->region = $this->resolve('region', $this->region)
            ->fromInput(fn (?string $value) => select(
                label: 'Application region',
                options: collect(CloudRegion::cases())->mapWithKeys(
                    fn (CloudRegion $region) => [
                        $region->value => $region->label(),
                    ],
                ),
                default: $value ?? $this->getDefaultRegion(),
                required: true,
            ))
            ->nonInteractively(fn () => $this->getDefaultRegion())
            ->errors($errors)
            ->value();

        return spin(
            fn () => $this->client->createApplication(
                $this->repository,
                $this->applicationName,
                $this->region,
            ),
            'Creating application...'
        );
    }

    protected function getDefaultRegion(): ?string
    {
        if ($this->defaultRegion) {
            return $this->defaultRegion;
        }

        $applications = spin(
            fn () => $this->client->listApplications(),
            'Fetching applications...'
        );

        $mostUsedRegion = collect($applications->data)
            ->pluck('region')
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $this->defaultRegion = CloudRegion::tryFrom($mostUsedRegion ?? '')?->value ?? CloudRegion::US_EAST_2->value;

        return $this->defaultRegion;
    }
}

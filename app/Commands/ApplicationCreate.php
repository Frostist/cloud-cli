<?php

namespace App\Commands;

use App\Concerns\DeterminesDefaultRegion;
use App\Concerns\RequiresRemoteGitRepo;
use App\Dto\Region;
use App\Git;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class ApplicationCreate extends BaseCommand
{
    use DeterminesDefaultRegion;
    use RequiresRemoteGitRepo;

    protected $signature = 'application:create
                            {--name= : Application name}
                            {--repository= : Repository (owner/repo format)}
                            {--region= : Application region}
                            {--json : Output as JSON}';

    protected $description = 'Create a new application';

    public function handle()
    {
        $this->ensureClient();

        intro('Creating Application');

        $application = $this->loopUntilValid($this->createApplication(...));

        $this->outputJsonIfWanted($application);

        success('Application created');

        $application = $this->client->applications()->get($application->id);

        outro($application->url());
    }

    protected function createApplication()
    {
        $git = app(Git::class);

        $this->fields()->add(
            'name',
            fn ($resolver) => $resolver->fromInput(
                fn ($currentValue) => text(
                    label: 'Application name',
                    default: $currentValue ?? basename(getcwd()),
                    required: true,
                ),
            ),
        );

        $this->fields()->add(
            'repository',
            fn ($resolver) => $resolver
                ->fromInput(fn (?string $value) => text(
                    label: 'Repository',
                    required: true,
                    default: $value ?? ($git->hasGitHubRemote() ? $git->remoteRepo() : ''),
                ))
                ->nonInteractively(fn () => $git->hasGitHubRemote() ? $git->remoteRepo() : null),
        );

        $regions = spin(
            fn () => $this->client->meta()->regions(),
            'Fetching regions...',
        );

        $this->fields()->add(
            'region',
            fn ($resolver) => $resolver
                ->fromInput(fn (?string $value) => select(
                    label: 'Region',
                    options: collect($regions)->mapWithKeys(fn (Region $region) => [
                        $region->value => $region->label,
                    ]),
                    default: $value ?? $this->getDefaultRegion(),
                    required: true,
                ))
                ->nonInteractively(fn () => $this->getDefaultRegion()),
        );

        return spin(
            fn () => $this->client->applications()->create(
                $this->fields()->get('repository'),
                $this->fields()->get('name'),
                $this->fields()->get('region'),
            ),
            'Creating application...',
        );
    }
}

<?php

namespace App\Commands;

use App\Git;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class EnvironmentCreate extends BaseCommand
{
    protected $signature = 'environment:create
                            {application? : The application ID}
                            {--name= : Environment name}
                            {--branch= : Git branch}
                            {--json : Output as JSON}';

    protected $description = 'Create a new environment';

    public function handle()
    {
        $this->ensureClient();

        intro('Creating Environment');

        $application = $this->resolvers()->application()->from($this->argument('application'));

        $environment = $this->loopUntilValid(
            fn () => $this->createEnvironment($application->id),
        );

        $this->outputJsonIfWanted($environment);

        outro("Environment created: {$environment->name}");
    }

    protected function createEnvironment(string $applicationId)
    {
        $currentBranch = app(Git::class)->currentBranch();

        $this->addParam(
            'name',
            fn ($resolver) => $resolver->fromInput(fn ($value) => text(
                label: 'Name',
                default: $this->getParam('name') ?? $currentBranch,
                required: true,
            )),
        );

        $this->addParam(
            'branch',
            fn ($resolver) => $resolver->fromInput(fn ($value) => text(
                label: 'Branch',
                default: $this->getParam('branch') ?? $currentBranch,
                required: true,
            )),
        );

        return spin(
            fn () => $this->client->environments()->create(
                $applicationId,
                $this->getParam('name'),
                $this->getParam('branch'),
            ),
            'Creating environment...',
        );
    }
}

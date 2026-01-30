<?php

namespace App\Resolvers;

use App\Dto\Application as ApplicationDto;
use App\Git;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class Application extends Resolver
{
    public function resolve(?string $idOrName = null): ?ApplicationDto
    {
        $identifier = $idOrName ?? $this->localConfig->get('application_id');

        return ($identifier ? $this->fromIdentifier($identifier) : null)
            ?? $this->fromRepo()
            ?? $this->fromInput();
    }

    public function fromIdentifier(string $identifier): ?ApplicationDto
    {
        if (str_starts_with($identifier, 'app-')) {
            try {
                return spin(
                    fn () => $this->client->applications()->withDefaultIncludes()->get($identifier),
                    'Fetching application...',
                );
            } catch (Throwable $e) {
                return $this->fetchAndFind($identifier);
            }
        }

        $app = $this->fetchAndFind($identifier);

        if (! $app) {
            throw new RuntimeException("Application '{$identifier}' not found.");
        }

        $this->displayResolved('Application', $app->name);

        return $app;
    }

    public function fromRepo(): ?ApplicationDto
    {
        $repository = app(Git::class)->remoteRepo();
        $apps = $this->fetchAll();

        return $apps->firstWhere('repositoryFullName', $repository);
    }

    public function fromInput(): ?ApplicationDto
    {
        $apps = $this->fetchAll();

        if ($apps->hasSole()) {
            $app = $apps->first();

            $this->displayResolved('Application', $app->name);

            return $app;
        }

        $this->ensureInteractive('Please provide an application ID or name.');

        $selectedApp = select(
            label: 'Application',
            options: $apps->mapWithKeys(fn ($app) => [$app->id => $app->name]),
        );

        return $apps->fromCollection($apps, $selectedApp);
    }

    public function fromCollection(Collection|LazyCollection $apps, string $identifier): ?ApplicationDto
    {
        return $apps->firstWhere('id', $identifier) ?? $apps->firstWhere('name', $identifier);
    }

    public function fetchAndFind(string $identifier): ?ApplicationDto
    {
        return $this->fromCollection($this->fetchAll(), $identifier);
    }

    protected function fetchAll(): Collection|LazyCollection
    {
        return collect(spin(
            fn () => $this->client->applications()->withDefaultIncludes()->list()->items(),
            'Fetching applications...',
        ));
    }
}

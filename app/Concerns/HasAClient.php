<?php

namespace App\Concerns;

use App\CloudClient;
use App\ConfigRepository;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

trait HasAClient
{
    protected CloudClient $client;

    protected function ensureClient()
    {
        $apiToken = $this->resolveApiToken();

        $this->client = new CloudClient($apiToken);
    }

    protected function ensureApiTokenExists(): void
    {
        $config = app(ConfigRepository::class);
        $apiTokens = $config->apiTokens();

        if ($apiTokens->isNotEmpty()) {
            return;
        }

        $this->resolveApiToken();
    }

    protected function resolveApiToken(): string
    {
        $config = app(ConfigRepository::class);
        $apiTokens = $config->apiTokens();

        if ($apiTokens->containsOneItem()) {
            return $apiTokens->first();
        }

        if ($apiTokens->containsManyItems()) {
            // TODO: Refactor once we have proper endpoints for orgs
            $orgs = spin(
                function () use ($apiTokens) {
                    return $apiTokens->mapWithKeys(function ($token) {
                        $client = new CloudClient($token);

                        $application = $client->listApplications()->data[0] ?? null;

                        if (! $application || ! $application->organization) {
                            return [$token => $token];
                        }

                        return [$token => $application->organization->name];
                    });
                },
                'Fetching token details'
            );

            $apiToken = select(
                label: 'Select an organization',
                options: $orgs->toArray(),
            );

            return $apiToken;
        }

        info('No API tokens found!');
        info('Learn how to create an API token: https://cloud.laravel.com/docs/api/authentication#create-an-api-token');

        $apiToken = password(
            label: 'Laravel Cloud API token',
            required: true,
        );

        $config->addApiToken($apiToken);

        info('API token saved to '.$config->path());

        return $apiToken;
    }
}

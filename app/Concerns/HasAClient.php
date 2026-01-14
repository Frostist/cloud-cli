<?php

namespace App\Concerns;

use App\CloudClient;
use App\ConfigRepository;

use function Laravel\Prompts\password;

trait HasAClient
{
    protected CloudClient $client;

    protected function ensureClient()
    {
        $config = app(ConfigRepository::class);

        $apiKey = $config->get('api_key');

        if (! $apiKey) {
            info('No API key found!');
            info('Learn how to generate a key: https://cloud.laravel.com/docs/api/authentication#create-an-api-token');

            $apiKey = password(
                label: 'Laravel Cloud API key',
                required: true,
            );

            $config->set('api_key', $apiKey);

            info('API key saved to '.$config->path());
        }

        $this->client = new CloudClient($apiKey);
    }
}

<?php

namespace App\Commands;

use App\ConfigRepository;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\password;

class Deploy extends Command
{
    protected $signature = 'deploy';

    protected $description = 'Deploy the application to Laravel Cloud';

    public function handle(ConfigRepository $config)
    {
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
    }
}

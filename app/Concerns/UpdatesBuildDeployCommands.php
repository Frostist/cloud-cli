<?php

namespace App\Concerns;

use App\Dto\Environment;

use function Laravel\Prompts\textarea;

trait UpdatesBuildDeployCommands
{
    protected function updateCommands(Environment $environment): void
    {
        $data = [];

        $data['build_command'] = textarea(
            label: 'Build command',
            default: $environment->buildCommand,
            required: true,
        );

        $data['deploy_command'] = textarea(
            label: 'Deploy command',
            default: $environment->deployCommand,
            required: true,
        );

        $this->loopUntilValid(
            function () use ($environment, $data) {
                return dynamicSpinner(
                    fn () => $this->client->environments()->update($environment->id, $data),
                    'Updating commands',
                );
            },
        );
    }
}

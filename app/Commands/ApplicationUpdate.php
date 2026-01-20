<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\Concerns\RequiresApplication;
use App\Concerns\Validates;
use Laravel\Prompts\Concerns\Colors;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class ApplicationUpdate extends Command
{
    use Colors;
    use HasAClient;
    use RequiresApplication;
    use Validates;

    protected $signature = 'application:update
                            {application? : The application ID or name}
                            {--name= : Application name}
                            {--slack-channel= : Slack channel for notifications}
                            {--json : Output as JSON}';

    protected $description = 'Update an application';

    public function handle()
    {
        $this->ensureClient();

        if (! $this->option('json')) {
            if ($this->argument('application')) {
                intro('Updating application: '.$this->argument('application'));
            } else {
                intro('Updating application');
            }
        }

        $application = $this->getCloudApplication(showPrompt: false);

        $data = [];

        if ($this->option('name')) {
            $data['name'] = $this->option('name');
        }

        if ($this->option('slack-channel')) {
            $data['slack_channel'] = $this->option('slack-channel');
        }

        if (empty($data)) {
            error('No fields to update. Provide at least one option.');

            return 1;
        }

        $application = $this->loopUntilValid(
            fn ($errors) => $this->client->updateApplication($application->id, $data),
            'Updating application'
        );

        if ($this->option('json')) {
            $this->line($application->toJson());

            return;
        }

        if (! $this->option('json')) {
            outro("Application updated: {$application->name}");
        }
    }
}

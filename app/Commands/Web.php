<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\Concerns\RequiresApplication;
use App\Concerns\RequiresRemoteGitRepo;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class Web extends BaseCommand
{
    use HasAClient;
    use RequiresApplication;
    use RequiresRemoteGitRepo;

    protected $signature = 'web
                            {application? : The application ID or name}';

    protected $description = 'Open the application in the Cloud dashboard';

    public function handle()
    {
        intro('Opening Cloud Dashboard');

        $this->ensureClient();
        $this->ensureRemoteGitRepo();

        $app = $this->resolvers()->application()->from($this->argument('application'));

        if (! $app) {
            if ($this->argument('application')) {
                $this->failAndExit('Unable to resolve application: '.$this->argument('application'));
            }

            $this->failAndExit('Unable to resolve application from current directory, provide an application ID or name as an argument.');
        }

        $url = $app->url();

        Process::run('open '.$url);

        outro($url);
    }
}

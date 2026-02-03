<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\Concerns\InteractsWithClipbboard;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class CommandGet extends BaseCommand
{
    use HasAClient;
    use InteractsWithClipbboard;

    protected $signature = 'command:get {commandId? : The command ID} {--json : Output as JSON} {--copy-output : Copy the output to the clipboard}';

    protected $description = 'Get command details';

    public function handle()
    {
        $this->ensureClient();

        intro('Command Details');

        $cmd = $this->resolvers()->command()->from($this->argument('commandId'));

        $this->outputJsonIfWanted($cmd);

        dataList([
            'ID' => $cmd->id,
            'Command' => $cmd->command,
            'Status' => $cmd->status->label(),
            'Exit Code' => $cmd->exitCode ?? '—',
            'Output' => $cmd->output ?? '—',
            'Started At' => $cmd->startedAt?->format('Y-m-d H:i:s') ?? '—',
            'Finished At' => $cmd->finishedAt?->format('Y-m-d H:i:s') ?? '—',
            'Created At' => $cmd->createdAt?->format('Y-m-d H:i:s') ?? '—',
            'Updated At' => $cmd->updatedAt?->format('Y-m-d H:i:s') ?? '—',
        ]);

        if ($this->option('copy-output')) {
            $this->copyToClipboard($cmd->output ?? '');
            success('Output copied to clipboard');
        }

        outro('');
    }
}

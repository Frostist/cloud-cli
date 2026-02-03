<?php

namespace App\Commands;

use App\Concerns\HasAClient;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;

class BackgroundProcessGet extends BaseCommand
{
    use HasAClient;

    protected $signature = 'background-process:get {process? : The background process ID} {--json : Output as JSON}';

    protected $description = 'Get background process details';

    public function handle()
    {
        $this->ensureClient();

        intro('Background Process Details');

        $process = $this->resolvers()->backgroundProcess()->from($this->argument('process'));

        $this->outputJsonIfWanted($process);

        info("Background Process: {$process->id}");

        dataList([
            'ID' => $process->id,
            'Command' => $process->command,
            'Instances' => $process->instances,
            'Type' => $process->type,
            'Queue' => $process->queue,
            'Connection' => $process->connection,
            'Timeout' => $process->timeout,
            'Sleep' => $process->sleep,
            'Tries' => $process->tries,
            'Max Processes' => $process->maxProcesses,
            'Min Processes' => $process->minProcesses,
            'Created At' => $process->createdAt?->format('Y-m-d H:i:s') ?? '—',
            'Updated At' => $process->updatedAt?->format('Y-m-d H:i:s') ?? '—',
            'Instance ID' => $process->instanceId,
        ]);
    }
}

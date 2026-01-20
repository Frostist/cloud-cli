<?php

namespace App\Commands;

use App\Concerns\HasAClient;
use App\Concerns\RequiresDatabaseCluster;
use Laravel\Prompts\Concerns\Colors;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\intro;

class DatabaseGet extends Command
{
    use Colors;
    use HasAClient;
    use RequiresDatabaseCluster;

    protected $signature = 'database:get {database? : The database ID or name} {--json : Output as JSON}';

    protected $description = 'Get database cluster details';

    public function handle()
    {
        $this->ensureClient();

        if (! $this->option('json')) {
            if ($this->argument('database')) {
                intro('Database Cluster Details: '.$this->argument('database'));
            } else {
                intro('Database Cluster Details');
            }
        }

        $database = $this->getDatabaseCluster(showPrompt: false);

        if ($this->option('json')) {
            $this->line($database->toJson());

            return;
        }

        dataList([
            'ID' => $database->id,
            'Name' => $database->name,
            'Type' => $database->type,
            'Status' => $database->status,
            'Region' => $database->region,
            'Schemas' => collect($database->schemas)->pluck('name')->toArray(),
        ]);
    }
}

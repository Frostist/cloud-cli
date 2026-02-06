<?php

namespace App\Commands;

use App\Concerns\CreatesDatabaseCluster;
use App\Concerns\DeterminesDefaultRegion;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class DatabaseClusterCreate extends BaseCommand
{
    use CreatesDatabaseCluster;
    use DeterminesDefaultRegion;

    protected $signature = 'database-cluster:create
                            {--name= : Database cluster name}
                            {--type= : Database type}
                            {--region= : Database region}
                            {--json : Output as JSON}';

    protected $description = 'Create a new database cluster';

    public function handle()
    {
        $this->ensureClient();

        intro('Create Database Cluster');

        $defaults = array_filter([
            'name' => $this->option('name'),
            'type' => $this->option('type'),
            'region' => $this->option('region') ?: $this->getDefaultRegion(),
        ]);

        $database = $this->loopUntilValid(
            fn () => $this->createDatabaseCluster($defaults),
        );

        $this->outputJsonIfWanted($database);

        success('Database cluster created');

        outro("Database cluster created: {$database->name}");
    }
}

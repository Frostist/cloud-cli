<?php

namespace App\Commands;

use App\Concerns\CreatesDatabase;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class DatabaseCreate extends BaseCommand
{
    use CreatesDatabase;

    protected $signature = 'database:create
                            {database-cluster? : The database cluster ID or name}
                            {--name= : Database (schema) name}
                            {--json : Output as JSON}';

    protected $description = 'Create a new database (schema) in a database cluster';

    public function handle()
    {
        $this->ensureClient();

        intro('Create Database');

        $cluster = $this->resolvers()->databaseCluster()->from($this->argument('database-cluster'));

        if ($this->option('name') && ! $this->isInteractive()) {
            $database = $this->loopUntilValid(
                fn () => $this->createDatabaseWithName($cluster, $this->option('name')),
            );
        } else {
            if (! $this->isInteractive()) {
                $this->failAndExit('Provide --name when non-interactive.');
            }

            $database = $this->loopUntilValid(fn () => $this->createDatabase($cluster));
        }

        $this->outputJsonIfWanted($database);

        success('Database created');

        outro("Database created: {$database->name}");
    }
}

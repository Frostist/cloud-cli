<?php

namespace App\Resolvers\Concerns;

use App\Dto\DatabaseCluster;

trait HasDatabaseCluster
{
    protected ?DatabaseCluster $databaseCluster = null;

    public function withCluster(null|string|DatabaseCluster $cluster): self
    {
        if (is_string($cluster)) {
            $cluster = $this->resolvers()->databaseCluster()->from($cluster);
        }

        $this->databaseCluster = $cluster;

        return $this;
    }

    protected function cluster(): DatabaseCluster
    {
        return $this->databaseCluster ??= $this->resolvers()->databaseCluster()->resolve();
    }
}

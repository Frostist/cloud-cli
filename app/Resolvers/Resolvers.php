<?php

namespace App\Resolvers;

use App\Client\Connector;
use App\LocalConfig;

class Resolvers
{
    public function __construct(
        protected Connector $client,
        protected LocalConfig $localConfig,
    ) {
        //
    }

    public function application(): Application
    {
        return new Application($this->client, $this->localConfig);
    }

    public function environment(): Environment
    {
        return new Environment($this->client, $this->localConfig);
    }
}

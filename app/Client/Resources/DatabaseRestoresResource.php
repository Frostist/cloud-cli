<?php

namespace App\Client\Resources;

use App\Client\Requests\CreateDatabaseRestoreRequestData;
use App\Client\Resources\DatabaseRestores\CreateDatabaseRestoreRequest;
use App\Dto\DatabaseCluster;

class DatabaseRestoresResource extends Resource
{
    public function create(CreateDatabaseRestoreRequestData $data): DatabaseCluster
    {
        $request = new CreateDatabaseRestoreRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }
}

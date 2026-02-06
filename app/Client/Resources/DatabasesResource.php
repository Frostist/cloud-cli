<?php

namespace App\Client\Resources;

use App\Client\Requests\CreateDatabaseRequestData;
use App\Client\Resources\Databases\CreateDatabaseRequest;
use App\Client\Resources\Databases\DeleteDatabaseRequest;
use App\Client\Resources\Databases\GetDatabaseRequest;
use App\Client\Resources\Databases\ListDatabasesRequest;
use App\Dto\Database;
use Saloon\PaginationPlugin\Paginator;

class DatabasesResource extends Resource
{
    public function list(string $clusterId): Paginator
    {
        $request = new ListDatabasesRequest($clusterId);

        return $this->paginate($request);
    }

    public function get(string $clusterId, string $databaseId): Database
    {
        $request = new GetDatabaseRequest(
            clusterId: $clusterId,
            databaseId: $databaseId,
        );
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(CreateDatabaseRequestData $data): Database
    {
        $request = new CreateDatabaseRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(string $clusterId, string $databaseId): void
    {
        $this->send(new DeleteDatabaseRequest(
            clusterId: $clusterId,
            databaseId: $databaseId,
        ));
    }
}

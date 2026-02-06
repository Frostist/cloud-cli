<?php

namespace App\Client\Resources;

use App\Client\Requests\CreateInstanceRequestData;
use App\Client\Requests\UpdateInstanceRequestData;
use App\Client\Resources\Instances\CreateInstanceRequest;
use App\Client\Resources\Instances\DeleteInstanceRequest;
use App\Client\Resources\Instances\GetInstanceRequest;
use App\Client\Resources\Instances\ListInstanceSizesRequest;
use App\Client\Resources\Instances\ListInstancesRequest;
use App\Client\Resources\Instances\UpdateInstanceRequest;
use App\Dto\EnvironmentInstance;
use Saloon\PaginationPlugin\Paginator;

class InstancesResource extends Resource
{
    public function list(string $environmentId): Paginator
    {
        $request = new ListInstancesRequest($environmentId);

        return $this->paginate($request);
    }

    public function get(string $instanceId): EnvironmentInstance
    {
        $request = new GetInstanceRequest($instanceId);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(CreateInstanceRequestData $data): EnvironmentInstance
    {
        $request = new CreateInstanceRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(UpdateInstanceRequestData $data): EnvironmentInstance
    {
        $request = new UpdateInstanceRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(string $instanceId): void
    {
        $this->send(new DeleteInstanceRequest($instanceId));
    }

    public function sizes(): array
    {
        $response = $this->send(new ListInstanceSizesRequest);

        return $response->json()['data'] ?? [];
    }
}

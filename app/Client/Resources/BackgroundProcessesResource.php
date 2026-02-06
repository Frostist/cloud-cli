<?php

namespace App\Client\Resources;

use App\Client\Requests\CreateBackgroundProcessRequestData;
use App\Client\Requests\UpdateBackgroundProcessRequestData;
use App\Client\Resources\BackgroundProcesses\CreateBackgroundProcessRequest;
use App\Client\Resources\BackgroundProcesses\DeleteBackgroundProcessRequest;
use App\Client\Resources\BackgroundProcesses\GetBackgroundProcessRequest;
use App\Client\Resources\BackgroundProcesses\ListBackgroundProcessesRequest;
use App\Client\Resources\BackgroundProcesses\UpdateBackgroundProcessRequest;
use App\Dto\BackgroundProcess;
use Saloon\PaginationPlugin\Paginator;

class BackgroundProcessesResource extends Resource
{
    public function list(string $instanceId): Paginator
    {
        $request = new ListBackgroundProcessesRequest($instanceId);

        return $this->paginate($request);
    }

    public function get(string $backgroundProcessId): BackgroundProcess
    {
        $request = new GetBackgroundProcessRequest($backgroundProcessId);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(CreateBackgroundProcessRequestData $data): BackgroundProcess
    {
        $request = new CreateBackgroundProcessRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(UpdateBackgroundProcessRequestData $data): BackgroundProcess
    {
        $request = new UpdateBackgroundProcessRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(string $backgroundProcessId): void
    {
        $this->send(new DeleteBackgroundProcessRequest($backgroundProcessId));
    }
}

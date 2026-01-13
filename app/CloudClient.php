<?php

namespace App;

use App\Dto\Deployment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CloudClient
{
    protected PendingRequest $client;

    public function __construct(
        protected string $apiKey,
    ) {
        $this->client = Http::withToken($this->apiKey)
            ->baseUrl('https://cloud.laravel.com/api')
            ->accept('application/json');
    }

    public function listApplications(): array
    {
        return $this->get('/applications');
    }

    public function createApplication(string $repository, string $name, string $region): array
    {
        return $this->post('/applications', [
            'repository' => $repository,
            'name' => $name,
            'region' => $region,
        ]);
    }

    public function getApplication(string $applicationId): array
    {
        return $this->get("/applications/{$applicationId}");
    }

    public function listEnvironments(string $applicationId): array
    {
        return $this->get("/applications/{$applicationId}/environments");
    }

    public function createEnvironment(string $applicationId, string $name, ?string $branch = null): array
    {
        return $this->post("/applications/{$applicationId}/environments", array_filter([
            'name' => $name,
            'branch' => $branch,
        ]));
    }

    public function initiateDeployment(string $environmentId): Deployment
    {
        $response = $this->post("/environments/{$environmentId}/deployments");

        return Deployment::fromApiResponse($response['data']);
    }

    public function getDeployment(string $deploymentId): Deployment
    {
        $response = $this->get("/deployments/{$deploymentId}");

        return Deployment::fromApiResponse($response['data']);
    }

    protected function get(string $endpoint): array
    {
        $response = $this->client->get($endpoint);

        return $response->json() ?? [];
    }

    protected function post(string $endpoint, array $data = []): array
    {
        $response = $this->client->post($endpoint, $data);

        return $response->json() ?? [];
    }
}

<?php

namespace App\Client\Resources\Environments;

use App\Dto\EnvironmentLog;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class ListEnvironmentLogsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $environmentId,
        protected string $from,
        protected string $to,
        protected ?string $cursor = null,
        protected ?string $type = null,
        protected ?string $queryString = null,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/environments/{$this->environmentId}/logs";
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'cursor' => $this->cursor,
            'type' => $this->type,
            'query' => $this->queryString,
        ]);
    }

    public function createDtoFromResponse(Response $response): array
    {
        $responseData = $response->json();

        return collect($responseData['data'] ?? [])->map(fn ($item) => EnvironmentLog::createFromResponse($item))->values()->all();
    }
}

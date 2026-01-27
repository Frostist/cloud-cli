<?php

namespace App\Dto;

use App\Enums\WebsocketServerConnectionDistributionStrategy;
use App\Enums\WebsocketServerMaxConnection;
use App\Enums\WebsocketServerStatus;
use App\Enums\WebsocketServerType;
use Carbon\CarbonImmutable;

class WebsocketCluster extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly WebsocketServerType $type,
        public readonly string $region,
        public readonly WebsocketServerStatus $status,
        public readonly WebsocketServerMaxConnection $maxConnections,
        public readonly WebsocketServerConnectionDistributionStrategy $connectionDistributionStrategy,
        public readonly string $hostname,
        public readonly ?CarbonImmutable $createdAt = null,
        public readonly array $applicationIds = [],
    ) {
        //
    }

    public static function fromApiResponse(array $response, ?array $item = null): self
    {
        $data = $item ?? $response['data'] ?? [];
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];

        $applicationIds = array_column($relationships['applications']['data'] ?? [], 'id');

        return new self(
            id: $data['id'],
            name: $attributes['name'],
            type: WebsocketServerType::from($attributes['type']),
            region: $attributes['region'],
            status: WebsocketServerStatus::from($attributes['status']),
            maxConnections: WebsocketServerMaxConnection::from($attributes['max_connections']),
            connectionDistributionStrategy: WebsocketServerConnectionDistributionStrategy::from($attributes['connection_distribution_strategy']),
            hostname: $attributes['hostname'],
            createdAt: isset($attributes['created_at']) ? CarbonImmutable::parse($attributes['created_at']) : null,
            applicationIds: $applicationIds,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'region' => $this->region,
            'status' => $this->status->value,
            'max_connections' => $this->maxConnections->value,
            'connection_distribution_strategy' => $this->connectionDistributionStrategy->value,
            'hostname' => $this->hostname,
            'created_at' => $this->createdAt?->toIso8601String(),
            'application_ids' => $this->applicationIds,
        ];
    }
}

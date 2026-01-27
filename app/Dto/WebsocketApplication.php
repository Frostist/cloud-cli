<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

class WebsocketApplication extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $appId,
        public readonly array $allowedOrigins,
        public readonly int $pingInterval,
        public readonly int $activityTimeout,
        public readonly int $maxMessageSize,
        public readonly int $maxConnections,
        public readonly string $key,
        public readonly string $secret,
        public readonly ?CarbonImmutable $createdAt = null,
        public readonly ?string $serverId = null,
        public readonly ?WebsocketCluster $server = null,
    ) {
        //
    }

    public static function fromApiResponse(array $response, ?array $item = null): self
    {
        $data = $item ?? $response['data'] ?? [];
        $included = $response['included'] ?? [];

        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];

        $serverIdentifier = $relationships['server']['data'] ?? null;
        $serverId = $serverIdentifier ? ($serverIdentifier['id'] ?? null) : null;

        $server = null;

        if ($serverId) {
            $serverData = collect($included)->first(fn ($item) => $item['type'] === 'websocketServers' && $item['id'] === $serverId);
            if ($serverData) {
                $server = WebsocketCluster::fromApiResponse(['data' => $serverData], $serverData);
            }
        }

        return new self(
            id: $data['id'],
            name: $attributes['name'],
            appId: $attributes['app_id'],
            allowedOrigins: $attributes['allowed_origins'] ?? [],
            pingInterval: $attributes['ping_interval'],
            activityTimeout: $attributes['activity_timeout'],
            maxMessageSize: $attributes['max_message_size'],
            maxConnections: $attributes['max_connections'],
            key: $attributes['key'],
            secret: $attributes['secret'],
            createdAt: isset($attributes['created_at']) ? CarbonImmutable::parse($attributes['created_at']) : null,
            serverId: $serverId,
            server: $server,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'app_id' => $this->appId,
            'allowed_origins' => $this->allowedOrigins,
            'ping_interval' => $this->pingInterval,
            'activity_timeout' => $this->activityTimeout,
            'max_message_size' => $this->maxMessageSize,
            'max_connections' => $this->maxConnections,
            'key' => $this->key,
            'secret' => $this->secret,
            'created_at' => $this->createdAt?->toIso8601String(),
            'server_id' => $this->serverId,
        ];
    }
}

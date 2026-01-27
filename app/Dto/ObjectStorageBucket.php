<?php

namespace App\Dto;

use App\Enums\FilesystemJurisdiction;
use App\Enums\FilesystemStatus;
use App\Enums\FilesystemType;
use App\Enums\FilesystemVisibility;
use Carbon\CarbonImmutable;

class ObjectStorageBucket extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly FilesystemType $type,
        public readonly FilesystemStatus $status,
        public readonly FilesystemVisibility $visibility,
        public readonly FilesystemJurisdiction $jurisdiction,
        public readonly ?string $endpoint = null,
        public readonly ?string $url = null,
        public readonly ?array $allowedOrigins = null,
        public readonly ?CarbonImmutable $createdAt = null,
        public readonly array $keyIds = [],
    ) {
        //
    }

    public static function fromApiResponse(array $response, ?array $item = null): self
    {
        $data = $item ?? $response['data'] ?? [];
        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];

        $keyIds = array_column($relationships['keys']['data'] ?? [], 'id');

        return new self(
            id: $data['id'],
            name: $attributes['name'],
            type: FilesystemType::from($attributes['type']),
            status: FilesystemStatus::from($attributes['status']),
            visibility: FilesystemVisibility::from($attributes['visibility']),
            jurisdiction: FilesystemJurisdiction::from($attributes['jurisdiction']),
            endpoint: $attributes['endpoint'] ?? null,
            url: $attributes['url'] ?? null,
            allowedOrigins: $attributes['allowed_origins'] ?? null,
            createdAt: isset($attributes['created_at']) ? CarbonImmutable::parse($attributes['created_at']) : null,
            keyIds: $keyIds,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'visibility' => $this->visibility->value,
            'jurisdiction' => $this->jurisdiction->value,
            'endpoint' => $this->endpoint,
            'url' => $this->url,
            'allowed_origins' => $this->allowedOrigins,
            'created_at' => $this->createdAt?->toIso8601String(),
            'key_ids' => $this->keyIds,
        ];
    }
}

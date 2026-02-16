<?php

namespace App\Dto;

use Spatie\LaravelData\Data;

class DedicatedCluster extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $region,
        public readonly string $status,
    ) {
        //
    }

    public static function createFromResponse(array $response): self
    {
        $data = $response['data'] ?? [];
        $attributes = $data['attributes'] ?? [];

        return self::from([
            'id' => $data['id'],
            'name' => $attributes['name'] ?? $data['id'],
            'region' => $attributes['region'] ?? '—',
            'status' => $attributes['status'] ?? '—',
        ]);
    }
}

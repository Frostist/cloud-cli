<?php

namespace App\Dto;

class Environment
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $branch = null,
        public readonly ?string $status = null,
        public readonly ?string $url = null,
    ) {
        //
    }

    public static function fromApiResponse(array $data): self
    {
        $attributes = $data['attributes'] ?? $data;

        return new self(
            id: $data['id'],
            name: $attributes['name'] ?? $data['name'],
            branch: $attributes['branch'] ?? null,
            status: $attributes['status'] ?? null,
            url: $attributes['vanity_url'] ?? $attributes['url'] ?? null,
        );
    }
}

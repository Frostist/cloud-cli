<?php

namespace App\Dto;

class Application
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $region,
        public readonly ?string $repositoryFullName = null,
        public readonly ?string $repositoryProvider = null,
        public readonly ?string $repositoryBranch = null,
    ) {
        //
    }

    public static function fromApiResponse(array $data): self
    {
        $attributes = $data['attributes'] ?? $data;
        $repository = $attributes['repository'] ?? [];

        return new self(
            id: $data['id'],
            name: $attributes['name'] ?? $data['name'],
            region: $attributes['region'] ?? $data['region'] ?? '',
            repositoryFullName: $repository['full_name'] ?? null,
            repositoryProvider: $repository['provider'] ?? null,
            repositoryBranch: $repository['default_branch'] ?? null,
        );
    }
}

<?php

namespace App\Dto;

class Organization extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
    ) {
        //
    }

    public static function fromApiResponse(array $response, ?array $item = null): self
    {
        $data = $item ?? $response['data'];
        $attributes = $data['attributes'];

        return new self(
            id: $data['id'],
            name: $attributes['name'],
            slug: $attributes['slug'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}

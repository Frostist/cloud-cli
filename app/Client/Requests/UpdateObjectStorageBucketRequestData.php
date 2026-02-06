<?php

namespace App\Client\Requests;

class UpdateObjectStorageBucketRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $bucketId,
        public readonly ?string $name = null,
        public readonly ?string $visibility = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'name' => $this->name,
            'visibility' => $this->visibility,
        ], fn ($value) => $value !== null);
    }
}

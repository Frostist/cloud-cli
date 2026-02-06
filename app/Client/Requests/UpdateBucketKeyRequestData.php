<?php

namespace App\Client\Requests;

class UpdateBucketKeyRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $bucketId,
        public readonly string $keyId,
        public readonly ?string $name = null,
        public readonly ?string $permission = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'name' => $this->name,
            'permission' => $this->permission,
        ], fn ($value) => $value !== null);
    }
}

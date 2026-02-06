<?php

namespace App\Client\Requests;

class UpdateWebSocketClusterRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $clusterId,
        public readonly ?string $name = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'name' => $this->name,
        ], fn ($value) => $value !== null);
    }
}

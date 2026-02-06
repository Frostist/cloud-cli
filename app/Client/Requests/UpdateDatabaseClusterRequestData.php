<?php

namespace App\Client\Requests;

class UpdateDatabaseClusterRequestData implements RequestDataInterface
{
    /**
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(
        public readonly string $clusterId,
        public readonly ?array $config = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'config' => $this->config,
        ], fn ($value) => $value !== null);
    }
}

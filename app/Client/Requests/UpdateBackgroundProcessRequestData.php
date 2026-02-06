<?php

namespace App\Client\Requests;

class UpdateBackgroundProcessRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $backgroundProcessId,
        public readonly ?string $command = null,
        public readonly ?int $instances = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'command' => $this->command,
            'instances' => $this->instances,
        ], fn ($value) => $value !== null);
    }
}

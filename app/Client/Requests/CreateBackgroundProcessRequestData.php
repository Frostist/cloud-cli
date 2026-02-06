<?php

namespace App\Client\Requests;

class CreateBackgroundProcessRequestData implements RequestDataInterface
{
    /**
     * @param  array<string, mixed>|null  $config  Worker config: queue, connection, tries, backoff, sleep, rest, timeout, force
     */
    public function __construct(
        public readonly string $instanceId,
        public readonly ?string $type = null,
        public readonly ?int $processes = null,
        public readonly ?string $command = null,
        public readonly ?array $config = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        $payload = array_filter([
            'type' => $this->type,
            'processes' => $this->processes,
            'command' => $this->command,
            'config' => $this->config,
        ], fn ($value) => $value !== null);

        return $payload;
    }
}

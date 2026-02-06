<?php

namespace App\Client\Requests;

class UpdateCacheRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $cacheId,
        public readonly ?string $name = null,
        public readonly ?string $size = null,
        public readonly ?bool $autoUpgradeEnabled = null,
        public readonly ?bool $isPublic = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'name' => $this->name,
            'size' => $this->size,
            'auto_upgrade_enabled' => $this->autoUpgradeEnabled,
            'is_public' => $this->isPublic,
        ], fn ($value) => $value !== null);
    }
}

<?php

namespace App\Client\Requests;

class UpdateDomainRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $domainId,
        public readonly ?string $verificationMethod = null,
        public readonly ?bool $isPrimary = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        return array_filter([
            'verification_method' => $this->verificationMethod,
            'is_primary' => $this->isPrimary,
        ], fn ($value) => $value !== null);
    }
}

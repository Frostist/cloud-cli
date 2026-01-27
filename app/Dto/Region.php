<?php

namespace App\Dto;

class Region extends Data
{
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly string $flag,
    ) {
        //
    }

    public static function fromApiResponse(array $response, ?array $item = null): self
    {
        $data = $item ?? $response['data'] ?? [];

        return new self(
            value: $data['region'],
            label: $data['label'],
            flag: $data['flag'],
        );
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'flag' => $this->flag,
        ];
    }
}

<?php

namespace App\Client\Resources\BucketKeys;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteBucketKeyRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected string $keyId,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/bucket-keys/{$this->keyId}";
    }
}

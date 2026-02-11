<?php

namespace App\Client\Resources\BucketKeys;

use App\Client\Resources\Concerns\AcceptsInclude;
use App\Dto\BucketKey;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetBucketKeyRequest extends Request
{
    use AcceptsInclude;

    protected Method $method = Method::GET;

    public function __construct(
        protected string $keyId,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return "/bucket-keys/{$this->keyId}";
    }

    public function createDtoFromResponse(Response $response): BucketKey
    {
        return BucketKey::createFromResponse($response->json());
    }
}

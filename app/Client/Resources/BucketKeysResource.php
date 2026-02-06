<?php

namespace App\Client\Resources;

use App\Client\Requests\CreateBucketKeyRequestData;
use App\Client\Requests\UpdateBucketKeyRequestData;
use App\Client\Resources\BucketKeys\CreateBucketKeyRequest;
use App\Client\Resources\BucketKeys\DeleteBucketKeyRequest;
use App\Client\Resources\BucketKeys\GetBucketKeyRequest;
use App\Client\Resources\BucketKeys\ListBucketKeysRequest;
use App\Client\Resources\BucketKeys\UpdateBucketKeyRequest;
use App\Dto\BucketKey;
use Saloon\PaginationPlugin\Paginator;

class BucketKeysResource extends Resource
{
    public function list(string $bucketId): Paginator
    {
        $request = new ListBucketKeysRequest($bucketId);

        return $this->paginate($request);
    }

    public function get(string $bucketId, string $keyId): BucketKey
    {
        $request = new GetBucketKeyRequest(
            bucketId: $bucketId,
            keyId: $keyId,
        );
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(CreateBucketKeyRequestData $data): BucketKey
    {
        $request = new CreateBucketKeyRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(UpdateBucketKeyRequestData $data): BucketKey
    {
        $request = new UpdateBucketKeyRequest($data);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(string $bucketId, string $keyId): void
    {
        $this->send(new DeleteBucketKeyRequest(
            bucketId: $bucketId,
            keyId: $keyId,
        ));
    }
}

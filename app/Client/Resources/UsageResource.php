<?php

namespace App\Client\Resources;

use App\Client\Resources\Usage\GetUsageRequest;
use App\Dto\BillingUsage;

class UsageResource extends Resource
{
    public function get(int $period = 0, ?string $environment = null): BillingUsage
    {
        $request = new GetUsageRequest($period, $environment);
        $response = $this->send($request);

        return $request->createDtoFromResponse($response);
    }
}

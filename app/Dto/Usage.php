<?php

namespace App\Dto;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class Usage extends Data
{
    public function __construct(
        public readonly int $currentSpendCents,
        public readonly string $currency,
        public readonly int $period,
        public readonly int $bandwidthCostCents,
        public readonly int $bandwidthUsagePercentage,
        public readonly int $bandwidthAllowanceBytes,
        public readonly int $creditsUsedCents,
        public readonly int $creditsTotalCents,
        public readonly ?int $alertThresholdCents,
        public readonly ?int $alertRemainingPercentage,
        public readonly int $resourcesTotalCostCents,
        public readonly int $addonsTotalCostCents,
        public readonly int $applicationCount,
        public readonly int $applicationsTotalCostCents,
        public readonly int $environmentUsageTotalCostCents,
        #[WithCast(DateTimeInterfaceCast::class, type: CarbonImmutable::class)]
        public readonly ?CarbonImmutable $lastUpdatedAt,
        public readonly array $availablePeriods = [],
        public readonly array $databases = [],
        public readonly array $caches = [],
        public readonly array $buckets = [],
        public readonly array $websockets = [],
        public readonly array $applications = [],
        public readonly array $addonItems = [],
        public readonly array $environmentUsageItems = [],
    ) {
        //
    }

    public static function createFromResponse(array $response): self
    {
        $data = $response['data'] ?? [];
        $meta = $response['meta'] ?? [];
        $summary = $data['summary'] ?? [];
        $bandwidth = $summary['bandwidth'] ?? [];
        $credits = $summary['credits'] ?? [];
        $alert = $summary['alert'] ?? [];
        $resources = $data['resources'] ?? [];
        $addons = $data['addons'] ?? [];
        $appTotals = $data['application_totals'] ?? [];
        $envUsage = $data['environment_usage'] ?? [];

        return self::from([
            'currentSpendCents' => $summary['current_spend_cents'] ?? 0,
            'currency' => $meta['currency'] ?? 'USD',
            'period' => $meta['period'] ?? 0,
            'bandwidthCostCents' => $bandwidth['cost_cents'] ?? 0,
            'bandwidthUsagePercentage' => (int) ($bandwidth['usage_percentage'] ?? 0),
            'bandwidthAllowanceBytes' => $bandwidth['allowance_bytes'] ?? 0,
            'creditsUsedCents' => $credits['used_cents'] ?? 0,
            'creditsTotalCents' => $credits['total_cents'] ?? 0,
            'alertThresholdCents' => $alert['threshold_cents'] ?? null,
            'alertRemainingPercentage' => isset($alert['remaining_percentage']) ? (int) $alert['remaining_percentage'] : null,
            'resourcesTotalCostCents' => $resources['total_cost_cents'] ?? 0,
            'addonsTotalCostCents' => $addons['total_cost_cents'] ?? 0,
            'applicationCount' => $appTotals['application_count'] ?? 0,
            'applicationsTotalCostCents' => $appTotals['total_cost_cents'] ?? 0,
            'environmentUsageTotalCostCents' => $envUsage['total_cost_cents'] ?? 0,
            'lastUpdatedAt' => isset($meta['last_updated_at']) ? CarbonImmutable::parse($meta['last_updated_at']) : null,
            'availablePeriods' => $meta['available_periods'] ?? [],
            'databases' => $resources['databases'] ?? [],
            'caches' => $resources['caches'] ?? [],
            'buckets' => $resources['buckets'] ?? [],
            'websockets' => $resources['websockets'] ?? [],
            'applications' => $appTotals['applications'] ?? [],
            'addonItems' => $addons['items'] ?? [],
            'environmentUsageItems' => $envUsage['items'] ?? [],
        ]);
    }

    public function periodDateRange(): ?string
    {
        $p = $this->availablePeriods[$this->period] ?? null;

        if (! $p || (! $p['from'] && ! $p['to'])) {
            return null;
        }

        $from = $p['from'] ? CarbonImmutable::parse($p['from'])->format('M j, Y') : null;
        $to = $p['to'] ? CarbonImmutable::parse($p['to'])->format('M j, Y') : null;

        return implode(' – ', array_filter([$from, $to]));
    }
}

<?php

namespace App\Commands;

use App\Dto\BillingUsage as BillingUsageDto;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\spin;

class BillingUsage extends BaseCommand
{
    protected ?string $jsonDataClass = BillingUsageDto::class;

    protected $signature = 'billing:usage
                            {--period=0 : Billing period offset (0=current, 1=previous, etc.)}
                            {--environment= : Filter usage by environment ID}
                            {--detailed : Show full breakdown with per-resource and per-application tables}';

    protected $description = 'View billing and usage for the current organization';

    public function handle()
    {
        $this->ensureClient();

        intro('Billing Usage');

        $period = (int) $this->option('period');
        $environmentId = $this->option('environment') ?: null;

        $usage = spin(
            fn () => $this->client->usage()->get($period, $environmentId),
            'Fetching billing usage...',
        );

        $this->outputJsonIfWanted($usage);

        $periodLabel = $usage->periodDateRange() ?? match ($period) {
            0 => 'Current',
            1 => 'Previous',
            default => "{$period} periods ago",
        };

        $bandwidthLine = $usage->bandwidthAllowanceBytes > 0
            ? $usage->bandwidthUsagePercentage.'% of '.$usage->formatBytes($usage->bandwidthAllowanceBytes).($usage->bandwidthCostCents > 0 ? ' ('.$usage->formatCents($usage->bandwidthCostCents).')' : '')
            : '—';

        dataList([
            'Period' => $periodLabel,
            'Total Spend' => $usage->formatCents($usage->currentSpendCents),
            'Bandwidth' => $bandwidthLine,
            'Credits Applied' => $usage->formatCents($usage->creditsUsedCents).' of '.$usage->formatCents($usage->creditsTotalCents),
            'Resources' => $usage->formatCents($usage->resourcesTotalCostCents),
            'Add-ons' => $usage->formatCents($usage->addonsTotalCostCents),
            'Applications' => $usage->formatCents($usage->applicationsTotalCostCents).' ('.($usage->applicationCount).' '.str('app')->plural($usage->applicationCount).')',
            'Currency' => $usage->currency,
            'Last Updated' => $usage->lastUpdatedAt?->format('Y-m-d H:i:s') ?? '—',
        ]);

        if (! $this->option('detailed') && ! $environmentId) {
            return self::SUCCESS;
        }

        if (! empty($usage->applications)) {
            info('Applications');
            dataTable(
                headers: ['Name', 'Cost'],
                rows: collect($usage->applications)->map(fn ($app) => [
                    $app['identifier'] ?? '—',
                    $usage->formatCents($app['total_cost_cents'] ?? 0),
                ])->toArray(),
            );
        }

        $resourceRows = collect([
            ['type' => 'Database', 'items' => $usage->databases],
            ['type' => 'Cache', 'items' => $usage->caches],
            ['type' => 'Bucket', 'items' => $usage->buckets],
            ['type' => 'Websocket', 'items' => $usage->websockets],
        ])
            ->filter(fn ($r) => ! empty($r['items']))
            ->flatMap(fn ($r) => collect($r['items'])->map(fn ($item) => [
                $r['type'],
                $item['name'] ?? $item['id'] ?? '—',
                $usage->formatCents($item['total_cents'] ?? 0),
            ]))
            ->toArray();

        if (! empty($resourceRows)) {
            info('Resources');
            dataTable(
                headers: ['Type', 'Name', 'Cost'],
                rows: $resourceRows,
            );
        }

        if (! empty($usage->environmentUsageItems)) {
            info('Environment Usage');
            dataTable(
                headers: ['Identifier', 'Type', 'Profile', 'CPU Hours', 'Cost'],
                rows: collect($usage->environmentUsageItems)->map(fn ($item) => [
                    $item['identifier'] ?? '—',
                    $item['type'] ?? '—',
                    trim(($item['compute_profile'] ?? '').' '.($item['compute_description'] ?? '')),
                    number_format($item['cpu_hours'] ?? 0, 2),
                    $usage->formatCents($item['total_cents'] ?? 0),
                ])->toArray(),
            );
        }

        if (! empty($usage->addonItems)) {
            info('Add-ons');
            dataTable(
                headers: ['Name', 'Cost'],
                rows: collect($usage->addonItems)->map(fn ($item) => [
                    $item['name'] ?? $item['id'] ?? '—',
                    $usage->formatCents($item['total_cents'] ?? 0),
                ])->toArray(),
            );
        }

        return self::SUCCESS;
    }
}

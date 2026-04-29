<?php

namespace App\Commands;

use App\Dto\Usage as UsageDto;
use App\Support\Formatter;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class Usage extends BaseCommand
{
    protected ?string $jsonDataClass = UsageDto::class;

    protected $signature = 'usage
                            {--period=0 : Usage period offset (0=current, 1=previous, etc.)}
                            {--environment= : Filter usage by environment ID or name}
                            {--detailed : Show full breakdown with per-resource and per-application tables}';

    protected $description = 'View billing and usage for the current organization';

    public function handle()
    {
        $this->ensureClient();

        intro('Usage');

        $period = (int) $this->option('period');
        $environmentId = $this->option('environment')
            ? $this->resolvers()->environment()->from($this->option('environment'))->id
            : null;

        $usage = spin(
            fn () => $this->client->usage()->get($period, $environmentId),
            'Fetching usage...',
        );

        $this->outputJsonIfWanted($usage);

        $periodLabel = $usage->periodDateRange() ?? match ($period) {
            0 => 'Current',
            1 => 'Previous',
            default => "{$period} periods ago",
        };

        $bandwidthLine = $usage->bandwidthAllowanceBytes > 0
            ? sprintf(
                '%d%% of %s%s',
                $usage->bandwidthUsagePercentage,
                $usage->formatBytes($usage->bandwidthAllowanceBytes),
                $usage->bandwidthCostCents > 0 ? ' ('.$usage->formatCents($usage->bandwidthCostCents).')' : '',
            )
            : '-';

        dataList(array_filter([
            'Period' => $periodLabel,
            'Total Spend' => Formatter::centsToDollars($usage->currentSpendCents),
            'Credits Applied' => $usage->creditsTotalCents === 0 ? null : Formatter::centsToDollars($usage->creditsUsedCents).' of '.Formatter::centsToDollars($usage->creditsTotalCents),
            'Applications' => Formatter::centsToDollars($usage->applicationsTotalCostCents).' ('.($usage->applicationCount).' '.str('app')->plural($usage->applicationCount).')',
            'Resources' => Formatter::centsToDollars($usage->resourcesTotalCostCents),
            'Add-ons' => Formatter::centsToDollars($usage->addonsTotalCostCents),
            'Currency' => $usage->currency,
            'Bandwidth' => $bandwidthLine,
            'Last Updated' => $usage->lastUpdatedAt?->format('Y-m-d H:i:s') ?? '—',
        ], fn ($value) => $value !== null));

        if (! $this->option('detailed') && ! $environmentId) {
            return self::SUCCESS;
        }

        if (! empty($usage->applications)) {
            $allApplications = spin(
                fn () => $this->client->applications()->list()->collect()->collect()->keyBy('id')->mapWithKeys(fn ($app) => [$app->id => $app->name]),
                'Fetching application details...',
            );

            info('Applications '.$this->dim(Formatter::centsToDollars(collect($usage->applications)->sum('total_cost_cents'))));

            table(
                headers: ['Name', 'Cost'],
                rows: collect($usage->applications)->sortBy('total_cost_cents')->values()->map(fn ($app, $i) => [
                    ($allApplications->get($app['identifier']) ?? $app['identifier'])."\n".$this->dim(str($app['identifier'])->limit(20)).($i === count($usage->applications) - 1 ? '' : "\n"),
                    $this->formatTotal($app['total_cost_cents'] ?? 0),
                ])->toArray(),
            );
        }

        $this->renderDatabaseUsage($usage->databases);
        $this->renderCacheUsage($usage->caches);
        $this->renderBucketUsage($usage->buckets);
        $this->renderWebsocketUsage($usage->websockets);

        if (! empty($usage->environmentUsageItems)) {
            info('Environment Usage');

            table(
                headers: ['Identifier', 'Type', 'Profile', 'CPU Hours', 'Cost'],
                rows: collect($usage->environmentUsageItems)->map(fn ($item) => [
                    $item['identifier'] ?? '—',
                    $item['type'] ?? '—',
                    trim(($item['compute_profile'] ?? '').' '.($item['compute_description'] ?? '')),
                    number_format($item['cpu_hours'] ?? 0, 2),
                    Formatter::centsToDollars($item['total_cents'] ?? 0),
                ])->toArray(),
            );
        }

        if (! empty($usage->addonItems)) {
            info('Add-ons');

            table(
                headers: ['Name', 'Cost'],
                rows: collect($usage->addonItems)->map(fn ($item) => [
                    $item['name'] ?? $item['id'] ?? '—',
                    Formatter::centsToDollars($item['total_cents'] ?? 0),
                ])->toArray(),
            );
        }

        return self::SUCCESS;
    }

    protected function renderDatabaseUsage(array $databases): void
    {
        if ($databases === []) {
            return;
        }

        info('Databases '.$this->dim(Formatter::centsToDollars(collect($databases)->sum('total_cents'))));

        table(
            headers: [
                'Name',
                'Type',
                'Storage',
                'Compute',
                'Backups',
                'Total',
            ],
            rows: collect($databases)->map(fn ($item, $i) => [
                $item['name']."\n".$this->dim(str($item['identifier'])->limit(20)),
                wordwrap($item['type'], 20, "\n").($i === count($databases) - 1 ? '' : "\n"),
                $this->formatTotal($item['storage_cents'] ?? 0)."\n".$this->dim(Formatter::gigabyte($item['storage_gb'] ?? 0)),
                $this->formatTotal($item['compute_cents'] ?? 0)."\n".$this->dim(round($item['compute_units'] ?? 0, 1).' '.$item['compute_unit_label']),
                $this->formatTotal($item['backups_cents'] ?? 0)."\n".$this->dim(Formatter::gigabyte($item['backups_gb'] ?? 0)),
                $this->formatTotal($item['total_cents'] ?? 0),
            ])->toArray(),
        );
    }

    protected function renderCacheUsage(array $caches): void
    {
        if ($caches === []) {
            return;
        }

        info('Caches '.$this->dim(Formatter::centsToDollars(collect($caches)->sum('total_cents'))));

        table(
            headers: [
                'Name',
                'Type',
                'Storage',
                'Compute',
                'Total',
            ],
            rows: collect($caches)->map(fn ($item, $i) => [
                $item['name']."\n".$this->dim(str($item['identifier'])->limit(20)),
                wordwrap($item['type'], 20, "\n").($i === count($caches) - 1 ? '' : "\n"),
                $this->formatTotal($item['storage_cents'] ?? 0)."\n".$this->dim(Formatter::gigabyte($item['storage_gb'] ?? 0)),
                $this->formatTotal($item['compute_cents'] ?? 0)."\n".$this->dim(str('hour')->plural($item['compute_hours'] ?? 0, true)),
                $this->formatTotal($item['total_cents'] ?? 0),
            ])->toArray(),
        );
    }

    protected function renderBucketUsage(array $buckets): void
    {
        if ($buckets === []) {
            return;
        }

        info('Buckets '.$this->dim(Formatter::centsToDollars(collect($buckets)->sum('total_cents'))));

        table(
            headers: [
                'Name',
                'Class A Requests',
                'Class B Requests',
                'Storage',
                'Total',
            ],
            rows: collect($buckets)->map(fn ($item, $i) => [
                $item['name']."\n".$this->dim(str($item['identifier'])->limit(20)).($i === count($buckets) - 1 ? '' : "\n"),
                $this->formatTotal($item['class_a_requests_cents'] ?? 0)."\n".$this->dim(str('request')->plural($item['class_a_requests_count'] ?? 0, true)),
                $this->formatTotal($item['class_b_requests_cents'] ?? 0)."\n".$this->dim(str('request')->plural($item['class_b_requests_count'] ?? 0, true)),
                $this->formatTotal($item['storage_cents'] ?? 0)."\n".$this->dim(Formatter::gigabyte($item['storage_gb'] ?? 0)),
                $this->formatTotal($item['total_cents'] ?? 0),
            ])->toArray(),
        );
    }

    protected function renderWebsocketUsage(array $websockets): void
    {
        if ($websockets === []) {
            return;
        }

        info('Buckets '.$this->dim(Formatter::centsToDollars(collect($websockets)->sum('total_cents'))));

        table(
            headers: [
                'Name',
                'Max Concurrent Connections',
                'Usage Time',
                'Total',
            ],
            rows: collect($websockets)->map(fn ($item, $i) => [
                $item['name']."\n".$this->dim(str($item['identifier'])->limit(20)).($i === count($websockets) - 1 ? '' : "\n"),
                $this->dim($item['max_connections']),
                $this->formatTotal($item['usage_time_cents'] ?? 0)."\n".$this->dim(str('hours')->plural($item['usage_time_hours'] ?? 0, true)),
                $this->formatTotal($item['total_cents'] ?? 0),
            ])->toArray(),
        );
    }

    protected function formatTotal(int $cents): string
    {
        $formatted = Formatter::centsToDollars($cents);

        if ($cents === 0) {
            return $formatted;
        }

        return $this->cyan($this->bold($formatted));
    }
}

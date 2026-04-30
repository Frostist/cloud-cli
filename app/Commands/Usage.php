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

        $bandwidthLine = $usage->bandwidthAllowanceBytes === 0
            ? '-'
            : sprintf(
                '%d%% of %s%s',
                $usage->bandwidthUsagePercentage,
                $usage->formatBytes($usage->bandwidthAllowanceBytes),
                $usage->bandwidthCostCents > 0 ? ' ('.$usage->formatCents($usage->bandwidthCostCents).')' : '',
            );

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

        if ($usage->environmentUsageItems !== []) {
            $this->totalsHeader('Environment Usage', $usage->environmentUsageTotalCostCents);

            table(
                headers: ['Identifier', 'Type', 'Profile', 'CPU Hours', 'Cost'],
                rows: collect($usage->environmentUsageItems)->map(fn ($item, $i) => [
                    $item['identifier'],
                    $item['type'],
                    $this->dataWithSubText(
                        $item['compute_profile'],
                        $item['compute_description'],
                        $i === count($usage->environmentUsageItems) - 1,
                    ),
                    number_format($item['cpu_hours'], 2),
                    $this->formatTotal($item['total_cents']),
                ])->toArray(),
            );
        }

        if (! $this->option('detailed')) {
            return self::SUCCESS;
        }

        if ($usage->applications !== []) {
            $allApplications = spin(
                fn () => $this->client->applications()->list()->collect()->collect()->keyBy('id')->mapWithKeys(fn ($app) => [$app->id => $app->name]),
                'Fetching application details...',
            );

            $this->totalsHeader('Applications', $usage->applicationsTotalCostCents);

            table(
                headers: ['Name', 'Cost'],
                rows: collect($usage->applications)
                    ->sortBy('total_cost_cents')
                    ->values()
                    ->map(fn ($app, $i) => [
                        $this->dataWithSubText(
                            $allApplications->get($app['identifier']) ?? $app['identifier'],
                            $app['identifier'],
                            $i === count($usage->applications) - 1,
                        ),
                        $this->formatTotal($app['total_cost_cents']),
                    ])
                    ->toArray(),
            );
        }

        $this->renderDatabaseUsage($usage->databases);
        $this->renderCacheUsage($usage->caches);
        $this->renderBucketUsage($usage->buckets);
        $this->renderWebsocketUsage($usage->websockets);

        if ($usage->addonItems !== []) {
            $this->totalsHeader('Add-ons', $usage->addonsTotalCostCents);

            table(
                headers: ['Name', 'Cost'],
                rows: collect($usage->addonItems)->map(fn ($item) => [
                    $item['name'],
                    $this->formatTotal($item['total_cents']),
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

        $this->totalsHeader('Databases', collect($databases)->sum('total_cents'));

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
                $this->dataWithSubText(
                    $item['name'],
                    $item['identifier'],
                    $i === count($databases) - 1,
                ),
                wordwrap($item['type'], 20, "\n"),
                $this->totalWithSubText(
                    $item['storage_cents'],
                    Formatter::gigabyte($item['storage_gb']),
                ),
                $this->totalWithSubText(
                    $item['compute_cents'],
                    round($item['compute_units'], 1).' '.$item['compute_unit_label'],
                ),
                $this->totalWithSubText(
                    $item['backups_cents'],
                    Formatter::gigabyte($item['backups_gb'] ?? 0),
                ),
                $this->formatTotal($item['total_cents']),
            ])->toArray(),
        );
    }

    protected function renderCacheUsage(array $caches): void
    {
        if ($caches === []) {
            return;
        }

        $this->totalsHeader('Caches', collect($caches)->sum('total_cents'));

        table(
            headers: [
                'Name',
                'Type',
                'Storage',
                'Compute',
                'Total',
            ],
            rows: collect($caches)->map(fn ($item, $i) => [
                $this->dataWithSubText(
                    $item['name'],
                    $item['identifier'],
                    $i === count($caches) - 1,
                ),
                wordwrap($item['type'], 20, "\n"),
                $this->dim($item['storage']),
                $this->totalWithSubText(
                    $item['compute_cents'],
                    str('hour')->plural($item['compute_hours'], true),
                ),
                $this->formatTotal($item['total_cents']),
            ])->toArray(),
        );
    }

    protected function renderBucketUsage(array $buckets): void
    {
        if ($buckets === []) {
            return;
        }

        $this->totalsHeader('Buckets', collect($buckets)->sum('total_cents'));

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
                $this->totalWithSubText(
                    $item['class_a_requests_cents'],
                    str('request')->plural($item['class_a_requests_count'], true),
                ),
                $this->totalWithSubText(
                    $item['class_b_requests_cents'],
                    str('request')->plural($item['class_b_requests_count'], true),
                ),
                $this->totalWithSubText(
                    $item['storage_cents'],
                    Formatter::gigabyte($item['storage_gb']),
                ),
                $this->formatTotal($item['total_cents']),
            ])->toArray(),
        );
    }

    protected function renderWebsocketUsage(array $websockets): void
    {
        if ($websockets === []) {
            return;
        }

        $this->totalsHeader('Websockets', collect($websockets)->sum('total_cents'));

        table(
            headers: [
                'Name',
                'Max Concurrent Connections',
                'Usage Time',
                'Total',
            ],
            rows: collect($websockets)->map(fn ($item, $i) => [
                $this->dataWithSubText(
                    $item['name'],
                    $item['identifier'],
                    $i === count($websockets) - 1,
                ),
                $this->dim($item['max_connections']),
                $this->totalWithSubText(
                    $item['usage_time_cents'],
                    str('hour')->plural($item['usage_time_hours'], true),
                ),
                $this->formatTotal($item['total_cents'] ?? 0),
            ])->toArray(),
        );
    }

    protected function totalsHeader(string $title, int $totalCents): void
    {
        info($title.' '.$this->dim(Formatter::centsToDollars($totalCents)));
    }

    protected function totalWithSubText(?int $cents, string $subText): string
    {
        return $this->formatTotal($cents ?? 0)."\n".$this->dim($subText);
    }

    protected function dataWithSubText(string $text, string $subText, bool $isLast = false): string
    {
        return $text."\n".$this->dim(str($subText)->limit(20)).($isLast ? '' : "\n");
    }

    protected function formatTotal(?int $cents): string
    {
        $cents ??= 0;

        $formatted = Formatter::centsToDollars($cents);

        if ($cents === 0) {
            return $formatted;
        }

        return $this->cyan($this->bold($formatted));
    }
}

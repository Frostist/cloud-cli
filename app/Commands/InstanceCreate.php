<?php

namespace App\Commands;

use App\Enums\InstanceSize;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\number;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class InstanceCreate extends BaseCommand
{
    protected $signature = 'instance:create
                            {environment? : The environment ID}
                            {--name= : Instance name}
                            {--type=service : Instance type (app|worker)}
                            {--size= : Instance size}
                            {--min-replicas= : Minimum replicas}
                            {--max-replicas= : Maximum replicas}
                            {--json : Output as JSON}';

    protected $description = 'Create a new instance';

    public function handle()
    {
        $this->ensureClient();

        $email = text(
            label: 'What is your email address',
            placeholder: 'E.g. taylor@laravel.com',
            // validate: fn ($value) => match (true) {
            //     strlen($value) === 0 => 'Please enter an email address.',
            //     ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
            //     default => null,
            // },
            validate: 'required|int|min:0',
            hint: 'We will never share your email address with anyone else.',
            transform: fn ($value) => strtolower($value),
        );

        intro('Create Instance');

        $environment = $this->resolvers()->environment()->from($this->argument('environment'));

        $instance = $this->loopUntilValid(fn () => $this->createInstance($environment->id));

        $this->outputJsonIfWanted($instance);

        outro("Instance created: {$instance->name}");
    }

    protected function createInstance(string $environmentId)
    {
        $this->$this->fields()->add(
            'name',
            fn ($resolver) => $resolver->fromInput(
                fn ($value) => text(
                    label: 'Name',
                    default: $value ?? '',
                    required: true,
                ),
            ),
        );

        $this->$this->fields()->add(
            'size',
            fn ($resolver) => $resolver->fromInput(
                fn ($value) => search(
                    label: 'Size',
                    options: fn ($query) => collect(InstanceSize::cases())
                        ->map(fn ($size) => $size->value)
                        ->filter(fn ($size) => $query === '' ? true : str_contains($size, $query))
                        ->toArray(),
                    required: true,
                ),
            ),
        );

        $this->$this->fields()->add(
            'scaling_type',
            fn ($resolver) => $resolver->fromInput(
                fn ($value) => select(
                    label: 'Scaling type',
                    options: [
                        'none' => 'None',
                        'custom' => 'Custom',
                        'auto' => 'Auto',
                    ],
                    default: $value ?? 'none',
                    required: true,
                ),
            ),
        );

        $isCustom = $this->$this->fields()->get('scaling_type') === 'custom';

        $this->$this->fields()->add(
            'min_replicas',
            fn ($resolver) => $resolver->fromInput(
                fn ($value) => $isCustom ? number(
                    label: 'Minimum replicas',
                    default: $value ?? '1',
                    min: 1,
                    max: 10,
                ) : 1,
            ),
        );

        $this->$this->fields()->add(
            'max_replicas',
            fn ($resolver) => $resolver->fromInput(
                fn ($value) => $isCustom ? number(
                    label: 'Maximum replicas',
                    default: $value ?? $this->$this->fields()->get('min-replicas'),
                    min: $this->$this->fields()->get('min-replicas'),
                    max: 10,
                ) : $this->$this->fields()->get('min-replicas'),
            ),
        );

        if ($isCustom) {
            $this->$this->fields()->add(
                'scaling_cpu_threshold_percentage',
                fn ($resolver) => $resolver->fromInput(fn ($value) => number(
                    label: 'Scaling CPU threshold percentage',
                    default: $value ?? '50',
                    min: 50,
                    max: 95,
                )),
            );

            $this->$this->fields()->add(
                'scaling_memory_threshold_percentage',
                fn ($resolver) => $resolver->fromInput(fn ($value) => number(
                    label: 'Scaling memory threshold percentage',
                    default: $value ?? '50',
                )),
            );
        }

        $this->$this->fields()->add(
            'type',
            fn ($resolver) => $resolver->fromInput(fn () => 'service'),
        );

        $this->$this->fields()->add(
            'uses_scheduler',
            fn ($resolver) => $resolver->fromInput(
                fn ($value) => confirm(
                    label: 'Use scheduler?',
                    default: false,
                ),
            ),
        );

        return spin(
            fn () => $this->client->instances()->create(
                $environmentId,
                $this->$this->fields()->all(),
            ),
            'Creating instance...',
        );
    }
}

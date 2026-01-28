<?php

namespace App\Commands;

use App\Concerns\HandlesAvatars;
use App\Concerns\HasAClient;
use App\Concerns\RequiresApplication;
use App\Concerns\Validates;
use App\Dto\Application;
use App\Git;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class ApplicationUpdate extends BaseCommand
{
    use HandlesAvatars;
    use HasAClient;
    use RequiresApplication;
    use Validates;

    protected $signature = 'application:update
                            {application? : The application ID or name}
                            {--name= : Application name}
                            {--slack-channel= : Slack channel for notifications}
                            {--repository= : Repository URL}
                            {--avatar= : Avatar URL or full path to a file}
                            {--default-environment= : Default environment ID or name}
                            {--json : Output as JSON}';

    protected $description = 'Update an application';

    public function handle()
    {
        $this->ensureClient();

        intro('Updating Application');

        $application = $this->getCloudApplication(showPrompt: false);

        $dataOptions = [
            'name' => [
                'label' => 'Name',
                'current' => $application->name,
            ],
            'slack-channel' => [
                'label' => 'Slack channel',
                'current' => $application->slackChannel ?? 'N/A',
                'key' => 'slack_channel',
            ],
            'repository' => [
                'label' => 'Repository',
                'current' => $application->repositoryFullName ?? 'N/A',
            ],
            'avatar' => [
                'label' => 'Avatar',
                'current' => 'N/A',
            ],
            'default-environment' => [
                'label' => 'Default environment',
                'current' => $application->defaultEnvironmentId ?? 'N/A',
                'key' => 'default_environment_id',
            ],
        ];

        $dataOptions = collect($dataOptions)->mapWithKeys(fn ($option, $key) => [
            $key => [
                ...$option,
                'key' => $option['key'] ?? $key,
            ],
        ])->toArray();

        $data = [];

        foreach ($dataOptions as $key => $option) {
            if ($this->option($key)) {
                $data[$option['key']] = $this->option($key);

                $this->reportChange(
                    $option['label'],
                    $option['current'],
                    $this->option($key),
                );
            }
        }

        if (! empty($data) || ! $this->isInteractive()) {
            if (empty($data)) {
                $this->outputErrorOrThrow('No fields to update. Provide at least one option.');

                return self::FAILURE;
            }

            $updatedApplication = spin(
                fn () => $this->client->applications()->update($application->id, $data),
                'Updating application...',
            );

            $this->outputJsonIfWanted($updatedApplication);

            outro('Application updated');

            return self::SUCCESS;
        }

        $application = $this->loopUntilValid(
            fn () => $this->collectDataAndUpdate($dataOptions, $application),
        );

        outro('Application updated');
    }

    protected function collectDataAndUpdate(array $dataOptions, Application $application): Application
    {
        $selection = multiselect(
            label: 'What do you want to update?',
            options: collect($dataOptions)->mapWithKeys(fn ($option, $key) => [
                $key => $option['label'],
            ])->toArray(),
        );

        if (empty($selection)) {
            $this->outputErrorOrThrow('No fields to update. Select at least one option.');

            exit(self::FAILURE);
        }

        foreach ($selection as $key) {
            $apiParamKey = $dataOptions[$key]['key'];

            $resolver = match ($key) {
                'name' => fn ($resolver) => $resolver->fromInput(
                    fn ($value) => $this->getNewName($value ?? $application->name),
                ),
                'slug' => fn ($resolver) => $resolver->fromInput(
                    fn ($value) => $this->getNewSlug($value ?? $application->slug),
                ),
                'repository' => fn ($resolver) => $resolver->fromInput(
                    fn ($value) => $this->getNewRepository($value ?? $application->repositoryFullName),
                ),
                'avatar' => fn ($resolver) => $resolver->fromInput(
                    fn ($value) => $this->getNewAvatar($value ?? $application->avatar),
                ),
                'default-environment' => fn ($resolver) => $resolver->fromInput(
                    fn ($value) => $this->getNewDefaultEnvironmentId($value ?? $application->defaultEnvironmentId),
                ),
                'slack-channel' => fn ($resolver) => $resolver->fromInput(
                    fn ($value) => $this->getNewSlackChannel($value ?? $application->slackChannel),
                ),
            };

            $this->addParam($apiParamKey, $resolver);
        }

        return spin(
            fn () => $this->client->applications()->update($application->id, $this->getParams()),
            'Updating application...',
        );
    }

    protected function reportChange(string $field, string $oldValue, string $newValue): void
    {
        dataList([
            $field => $this->dim($this->yellow($oldValue).' →').' '.$this->green($newValue),
        ]);
    }

    protected function getNewName(string $oldName): string
    {
        return text(
            label: 'Name',
            required: true,
            default: $oldName,
            validate: fn ($value) => match (true) {
                strlen($value) < 3 => 'Name must be at least 3 characters',
                strlen($value) > 40 => 'Name must be less than 40 characters',
                ! preg_match('/^[\p{Latin}0-9 _.\'-]+$/u', $value) => 'Name must contain only letters, numbers, spaces, and: _ . \' -',
                default => null,
            },
        );
    }

    protected function getNewSlug(string $oldSlug): string
    {
        return text(
            label: 'Slug',
            required: true,
            default: $oldSlug,
            validate: fn ($value) => match (true) {
                strlen($value) < 3 => 'Slug must be at least 3 characters',
                default => null,
            },
        );
    }

    protected function getNewRepository(string $oldRepository): string
    {
        return text(
            label: 'Repository',
            required: true,
            default: $oldRepository,
        );
    }

    protected function getNewAvatar(): array
    {
        $avatarCandidates = $this->getAvatarCandidatesFromRepo();

        if ($avatarCandidates->isNotEmpty()) {
            $root = app(Git::class)->getRoot();

            $options = $avatarCandidates->mapWithKeys(fn ($path) => [
                $path => str($path)->after($root)->ltrim(DIRECTORY_SEPARATOR)->toString(),
            ]);

            $options->offsetSet('custom', 'Custom');

            $selected = select(
                label: 'Avatar',
                options: $options,
            );

            if ($selected !== 'custom') {
                $extension = pathinfo($selected, PATHINFO_EXTENSION);

                if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
                    return [file_get_contents($selected), $extension];
                }

                $imagick = new \Imagick;
                $imagick->readImage($selected);
                $imagick->setImageFormat('png');

                return [$imagick->getImageBlob(), 'png'];
            }
        }

        $path = text(
            label: 'Avatar',
            required: true,
            hint: 'Path or URL to the avatar image',
            validate: fn ($value) => match (true) {
                ! file_exists($value) && ! filter_var($value, FILTER_VALIDATE_URL) => 'Invalid path or URL',
                default => null,
            },
        );

        return [
            file_get_contents($path),
            pathinfo($selected, PATHINFO_EXTENSION),
        ];
    }

    protected function getNewDefaultEnvironmentId(Application $application): string
    {
        $options = collect($application->environments)
            ->mapWithKeys(fn ($environment) => [
                $environment->id => $environment->name,
            ]);

        return select(
            label: 'Default environment',
            options: $options,
            required: true,
            default: $application->defaultEnvironmentId,
        );
    }

    protected function getNewSlackChannel(string $oldSlackChannel): string
    {
        return text(
            label: 'Slack channel',
            required: true,
            default: $oldSlackChannel,
        );
    }
}

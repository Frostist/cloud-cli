<?php

namespace App\Client\Requests;

use Saloon\Data\MultipartValue;

class UpdateApplicationRequestData implements RequestDataInterface
{
    public function __construct(
        public readonly string $applicationId,
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
        public readonly ?string $defaultEnvironmentId = null,
        public readonly ?string $repository = null,
        public readonly ?string $slackChannel = null,
        public readonly ?array $avatar = null,
    ) {
        //
    }

    public function toRequestData(): array
    {
        $body = array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'default_environment_id' => $this->defaultEnvironmentId,
            'repository' => $this->repository,
            'slack_channel' => $this->slackChannel,
        ]);

        if (isset($this->avatar) && is_array($this->avatar) && count($this->avatar) === 2) {
            [$avatarContent, $extension] = $this->avatar;

            $body['avatar'] = new MultipartValue(
                name: 'avatar',
                value: $avatarContent,
                filename: 'avatar.'.$extension,
            );
        }

        return $body;
    }
}

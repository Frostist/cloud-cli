<?php

namespace App\Dto;

use Carbon\CarbonImmutable;

class Deployment
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $commitHash = null,
        public readonly ?string $commitMessage = null,
        public readonly ?string $commitAuthor = null,
        public readonly ?CarbonImmutable $startedAt = null,
        public readonly ?CarbonImmutable $finishedAt = null,
    ) {
        //
    }

    public static function fromApiResponse(array $data): self
    {
        $attributes = $data['attributes'] ?? $data;
        $commit = $attributes['commit'] ?? [];

        return new self(
            id: $data['id'],
            status: $attributes['status'] ?? 'pending',
            commitHash: $commit['hash'] ?? $attributes['commit_hash'] ?? null,
            commitMessage: $commit['message'] ?? $attributes['commit_message'] ?? null,
            commitAuthor: $commit['author'] ?? $attributes['commit_author'] ?? null,
            startedAt: $attributes['started_at'] ? CarbonImmutable::parse($attributes['started_at']) : null,
            finishedAt: $attributes['finished_at'] ? CarbonImmutable::parse($attributes['finished_at']) : null,
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'deployment.pending';
    }

    public function isBuilding(): bool
    {
        return $this->status === 'deployment.building';
    }

    public function isDeploying(): bool
    {
        return $this->status === 'deployment.deploying';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'deployment.completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'deployment.failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'deployment.cancelled';
    }

    public function isFinished(): bool
    {
        return $this->isCompleted() || $this->isFailed() || $this->isCancelled();
    }

    public function isInProgress(): bool
    {
        return ! $this->isFinished();
    }
}

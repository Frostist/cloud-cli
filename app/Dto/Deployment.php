<?php

namespace App\Dto;

use App\Enums\DeploymentStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;

class Deployment
{
    public function __construct(
        public readonly string $id,
        public readonly DeploymentStatus $status,
        public readonly ?string $commitHash = null,
        public readonly ?string $commitMessage = null,
        public readonly ?string $commitAuthor = null,
        public readonly ?CarbonImmutable $startedAt = null,
        public readonly ?CarbonImmutable $finishedAt = null,
        public readonly ?CarbonImmutable $createdAt = null,
        public readonly ?CarbonImmutable $updatedAt = null,
        public readonly ?string $failureReason = null,
        public readonly string $branchName = '',
        public readonly string $phpMajorVersion = '',
        public readonly ?string $buildCommand = null,
        public readonly ?string $nodeVersion = null,
        public readonly bool $usesOctane = false,
        public readonly bool $usesHibernation = false,
        public readonly ?string $environmentId = null,
        public readonly ?string $initiatorId = null,
    ) {
        //
    }

    public static function fromApiResponse(array $response, ?array $item = null): self
    {
        $data = $item ?? $response['data'] ?? [];
        $included = $response['included'] ?? [];

        $attributes = $data['attributes'] ?? [];
        $relationships = $data['relationships'] ?? [];
        $commit = $attributes['commit'] ?? [];

        return new self(
            id: $data['id'],
            status: DeploymentStatus::from($attributes['status'] ?? 'pending'),
            commitHash: $commit['hash'] ?? $attributes['commit_hash'] ?? null,
            commitMessage: $commit['message'] ?? $attributes['commit_message'] ?? null,
            commitAuthor: $commit['author'] ?? $attributes['commit_author'] ?? null,
            startedAt: isset($attributes['started_at']) ? CarbonImmutable::parse($attributes['started_at']) : null,
            finishedAt: isset($attributes['finished_at']) ? CarbonImmutable::parse($attributes['finished_at']) : null,
            createdAt: isset($attributes['created_at']) ? CarbonImmutable::parse($attributes['created_at']) : null,
            updatedAt: isset($attributes['updated_at']) ? CarbonImmutable::parse($attributes['updated_at']) : null,
            failureReason: $attributes['failure_reason'] ?? null,
            branchName: $attributes['branch_name'] ?? '',
            phpMajorVersion: $attributes['php_major_version'] ?? '',
            buildCommand: $attributes['build_command'] ?? null,
            nodeVersion: $attributes['node_version'] ?? null,
            usesOctane: $attributes['uses_octane'] ?? false,
            usesHibernation: $attributes['uses_hibernation'] ?? false,
            environmentId: $relationships['environment']['data']['id'] ?? null,
            initiatorId: $relationships['initiator']['data']['id'] ?? null,
        );
    }

    public function totalTime(): CarbonInterval
    {
        if (! $this->startedAt || ! $this->finishedAt) {
            return CarbonInterval::seconds(0);
        }

        return $this->finishedAt->diff($this->startedAt);
    }

    public function timeElapsed(): CarbonInterval
    {
        if (! $this->startedAt) {
            return CarbonInterval::seconds(0);
        }

        return $this->startedAt->diff(CarbonImmutable::now());
    }

    public function isPending(): bool
    {
        return $this->status === DeploymentStatus::PENDING;
    }

    public function isBuilding(): bool
    {
        return $this->status === DeploymentStatus::BUILD_RUNNING;
    }

    public function isDeploying(): bool
    {
        return $this->status === DeploymentStatus::DEPLOYMENT_RUNNING;
    }

    public function succeeded(): bool
    {
        return $this->status === DeploymentStatus::DEPLOYMENT_SUCCEEDED;
    }

    public function failed(): bool
    {
        return $this->status === DeploymentStatus::DEPLOYMENT_FAILED || $this->status === DeploymentStatus::BUILD_FAILED;
    }

    public function wasCancelled(): bool
    {
        return $this->status === DeploymentStatus::CANCELLED;
    }

    public function isFinished(): bool
    {
        return $this->succeeded() || $this->failed() || $this->wasCancelled();
    }

    public function isInProgress(): bool
    {
        return ! $this->isFinished();
    }
}

<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case PENDING = 'pending';
    case BUILD_PENDING = 'build.pending';
    case BUILD_CREATED = 'build.created';
    case BUILD_QUEUED = 'build.queued';
    case BUILD_RUNNING = 'build.running';
    case BUILD_SUCCEEDED = 'build.succeeded';
    case BUILD_FAILED = 'build.failed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case DEPLOYMENT_PENDING = 'deployment.pending';
    case DEPLOYMENT_CREATED = 'deployment.created';
    case DEPLOYMENT_QUEUED = 'deployment.queued';
    case DEPLOYMENT_RUNNING = 'deployment.running';
    case DEPLOYMENT_SUCCEEDED = 'deployment.succeeded';
    case DEPLOYMENT_FAILED = 'deployment.failed';

    public function label(): string
    {
        return match ($this) {
            DeploymentStatus::BUILD_PENDING => 'Building...',
            DeploymentStatus::BUILD_CREATED => 'Build created...',
            DeploymentStatus::BUILD_QUEUED => 'Build queued...',
            DeploymentStatus::BUILD_RUNNING => 'Build running...',
            DeploymentStatus::BUILD_SUCCEEDED => 'Build succeeded!',
            DeploymentStatus::BUILD_FAILED => 'Build failed!',
            DeploymentStatus::DEPLOYMENT_PENDING => 'Deploying...',
            DeploymentStatus::DEPLOYMENT_CREATED => 'Deployment created...',
            DeploymentStatus::DEPLOYMENT_QUEUED => 'Deployment queued...',
            DeploymentStatus::DEPLOYMENT_RUNNING => 'Deployment running...',
            DeploymentStatus::DEPLOYMENT_SUCCEEDED => 'Deployment succeeded!',
            DeploymentStatus::DEPLOYMENT_FAILED => 'Deployment failed!',
            DeploymentStatus::CANCELLED => 'Cancelled!',
            DeploymentStatus::FAILED => 'Failed!',
            DeploymentStatus::PENDING => 'Pending...',
            default => $this->value,
        };
    }
}

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
}

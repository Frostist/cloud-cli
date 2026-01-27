<?php

namespace App\Enums;

enum WebsocketServerConnectionDistributionStrategy: string
{
    case EVENLY = 'evenly';
    case CUSTOM = 'custom';
}

<?php

namespace App\Enums;

enum WebsocketServerMaxConnection: int
{
    case ONE_HUNDRED = 100;
    case TWO_HUNDRED = 200;
    case FIVE_HUNDRED = 500;
    case TWO_THOUSAND = 2000;
    case FIVE_THOUSAND = 5000;
    case TEN_THOUSAND = 10000;
}

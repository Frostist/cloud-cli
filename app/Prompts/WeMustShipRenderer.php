<?php

namespace App\Prompts;

class WeMustShipRenderer extends Renderer
{
    public function __invoke(WeMustShip $weMustShip): string
    {
        return $weMustShip->lines->implode(PHP_EOL);
    }
}

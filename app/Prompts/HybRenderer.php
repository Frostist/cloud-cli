<?php

namespace App\Prompts;

class HybRenderer extends Renderer
{
    public function __invoke(Hyb $hyb): string
    {
        return PHP_EOL.$hyb->lines->implode(PHP_EOL).PHP_EOL.PHP_EOL.PHP_EOL;
    }
}

<?php

namespace App\Prompts;

class SlideInRenderer extends Renderer
{
    public function __invoke(SlideIn $slideIn): string
    {
        return PHP_EOL.$slideIn->lines->implode(PHP_EOL).PHP_EOL.PHP_EOL.PHP_EOL;
    }
}

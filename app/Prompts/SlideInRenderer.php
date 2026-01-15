<?php

namespace App\Prompts;

class SlideInRenderer extends Renderer
{
    public function __invoke(SlideIn $slideIn): string
    {
        return $slideIn->lines->implode(PHP_EOL);
    }
}

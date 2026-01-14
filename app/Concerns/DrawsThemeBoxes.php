<?php

namespace App\Concerns;

use App\Enums\TimelineSymbol;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;

trait DrawsThemeBoxes
{
    use DrawsBoxes {
        box as parentBox;
    }

    public function box(string $title, string $body, string $footer = '', string $color = 'gray', string $info = '', TimelineSymbol $symbol = TimelineSymbol::PENDING): self
    {
        $originalOutput = $this->output;
        $this->output = '';

        $this->parentBox($title, $body, $footer, $color, $info);

        $newOutput = collect(explode(PHP_EOL, $this->output))
            ->map(function ($line, $index) use ($symbol) {
                if (! strlen($line)) {
                    return $line;
                }

                return ($index === 0 ? $this->{TimelineSymbol::color($symbol)}($symbol->value) : TimelineSymbol::LINE->value).' '.$line;
            })
            ->implode(PHP_EOL);

        $this->output = $originalOutput.$newOutput;

        return $this;
    }
}

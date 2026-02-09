<?php

namespace App\Prompts;

use App\Concerns\CapturesOutput;
use App\Concerns\DrawsThemeBoxes;
use Laravel\Prompts\Themes\Default\Concerns\InteractsWithStrings;

class DataListRenderer extends Renderer
{
    use CapturesOutput;
    use DrawsThemeBoxes;
    use InteractsWithStrings;

    /**
     * Render the data list.
     */
    public function __invoke(DataList $prompt): string
    {
        $first = true;

        foreach ($prompt->data as $key => $value) {
            if ($first) {
                $this->bullet($this->dim($key));
                $first = false;
            } else {
                $this->lineWithBorder('');
                $this->lineWithBorder($this->dim($key));
            }

            $value = match (true) {
                is_array($value) => $value,
                $value === null, trim($value) === '' => ['—'],
                default => explode(PHP_EOL, $value),
            };

            $this->writeValue($value);
        }

        return $this;
    }

    protected function writeValue(array $value): void
    {
        if (! is_array($value[0] ?? null)) {
            $wrappedValue = $this->prepareValueForDisplay($value);

            foreach ($wrappedValue as $index => $item) {
                $this->lineWithBorder($this->green(trim($item)));
            }

            return;
        }

        foreach ($value as $item) {
            $wrappedValue = $this->prepareValueForDisplay([$item[0]]);

            foreach ($wrappedValue as $index => $line) {
                if ($index === 0) {
                    $suffix = ($item[1] ?? null) ? ' '.$this->dim(trim($item[1])) : '';
                    $this->lineWithBorder($this->green(trim($line).$suffix));
                } else {
                    $this->lineWithBorder($this->green(trim($line)));
                }
            }
        }
    }

    protected function prepareValueForDisplay(array $value): array
    {
        $value = implode(PHP_EOL, $value);
        $value = $this->mbWordwrap($value, $this->prompt->terminal()->cols() - 6, cut_long_words: true);
        $value = rtrim($value, PHP_EOL);

        return explode(PHP_EOL, $value);
    }
}

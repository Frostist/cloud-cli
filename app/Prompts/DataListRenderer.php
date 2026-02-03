<?php

namespace App\Prompts;

class DataListRenderer extends Renderer
{
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

            foreach ($value as $item) {
                $this->lineWithBorder($this->green(trim($item)));
            }
        }

        return $this;
    }
}

<?php

use App\Prompts\Answered;

if (! function_exists('answered')) {
    function answered(string $label, string $answer): void
    {
        (new Answered(label: $label, answer: $answer))->display();
    }
}

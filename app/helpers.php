<?php

use App\Prompts\Answered;
use Laravel\Prompts\Note;

if (! function_exists('answered')) {
    function answered(string $label, string $answer): void
    {
        (new Answered(label: $label, answer: $answer))->display();
    }
}

if (! function_exists('success')) {
    function success(string $message): void
    {
        (new Note(message: $message, type: 'success'))->display();
    }
}

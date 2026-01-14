<?php

namespace App\Providers;

use App\Prompts\Answered;
use App\Prompts\DynamicSpinner;
use App\Prompts\NoteRenderer;
use App\Prompts\SpinnerRenderer;
use App\Prompts\TextPromptRenderer;
use App\Prompts\WeMustShip;
use App\Prompts\WeMustShipRenderer;
use Illuminate\Support\ServiceProvider;
use Laravel\Prompts\Note;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Spinner;
use Laravel\Prompts\TextPrompt;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Prompt::addTheme('cloud', [
            DynamicSpinner::class => SpinnerRenderer::class,
            Note::class => NoteRenderer::class,
            Spinner::class => SpinnerRenderer::class,
            WeMustShip::class => WeMustShipRenderer::class,
            TextPrompt::class => TextPromptRenderer::class,
            Answered::class => TextPromptRenderer::class,
        ]);

        Prompt::theme('cloud');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}

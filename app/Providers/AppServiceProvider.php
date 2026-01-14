<?php

namespace App\Providers;

use App\Prompts\Answered;
use App\Prompts\DynamicSpinner;
use App\Prompts\MultiSelectPromptRenderer;
use App\Prompts\NoteRenderer;
use App\Prompts\PasswordPromptRenderer;
use App\Prompts\SelectPromptRenderer;
use App\Prompts\SpinnerRenderer;
use App\Prompts\TextPromptRenderer;
use App\Prompts\WeMustShip;
use App\Prompts\WeMustShipRenderer;
use Illuminate\Support\ServiceProvider;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\Note;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SelectPrompt;
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
            SelectPrompt::class => SelectPromptRenderer::class,
            MultiSelectPrompt::class => MultiSelectPromptRenderer::class,
            PasswordPrompt::class => PasswordPromptRenderer::class,
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

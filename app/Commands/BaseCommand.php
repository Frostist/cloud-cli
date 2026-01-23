<?php

namespace App\Commands;

use App\Dto\ValidationErrors;
use App\Support\ValueResolver;
use Laravel\Prompts\Concerns\Colors;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

abstract class BaseCommand extends Command
{
    use Colors;

    protected function intro(string $title, ?string $suffix = null): void
    {
        if ($this->wantsJson()) {
            return;
        }

        if ($suffix) {
            $title .= ': '.$suffix;
        }

        intro($title);
    }

    protected function outro(string $title): void
    {
        if ($this->wantsJson()) {
            return;
        }

        outro($title);
    }

    protected function ensureInteractive(string $message): void
    {
        if (! $this->isInteractive()) {
            throw new RuntimeException($message);
        }
    }

    protected function isInteractive(): bool
    {
        if ($this->option('no-interaction')) {
            return false;
        }

        if ($this->isNonInteractiveEnvironment()) {
            return false;
        }

        if (! stream_isatty(STDIN)) {
            return false;
        }

        if ($this->requestedJson()) {
            return false;
        }

        return true;
    }

    protected function isNonInteractiveEnvironment(): bool
    {
        $envs = [
            'CI',
            'CURSOR',
            'GITHUB_ACTIONS',
            'GITLAB_CI',
            'JENKINS_URL',
            'CIRCLECI',
            'TRAVIS',
            'AGENT_MODE',
        ];

        foreach ($envs as $env) {
            if (! empty(getenv($env))) {
                return true;
            }
        }

        return false;
    }

    protected function outputErrorOrThrow(string $message): void
    {
        if ($this->isInteractive()) {
            error($message);
        } else {
            throw new RuntimeException($message);
        }
    }

    protected function requestedJson(): bool
    {
        return $this->hasOption('json') && $this->option('json');
    }

    protected function wantsJson(): bool
    {
        if ($this->requestedJson() || ! $this->isInteractive()) {
            return true;
        }

        return false;
    }

    protected function outputJsonIfWanted(mixed $data): void
    {
        if ($this->wantsJson()) {
            $this->line($data->toJson());

            exit(0);
        }
    }

    protected function resolve(string $argument, ?string $value): ValueResolver
    {
        return new ValueResolver(
            $argument,
            $this->isInteractive(),
            $value ?? match (true) {
                $this->hasOption($argument) => $this->option($argument),
                $this->hasArgument($argument) => $this->argument($argument),
                default => null,
            },
            $this->hasOption($argument) ? 'option' : 'argument',
        );
    }

    protected function breakValidationLoopIfNotInteractive(ValidationErrors $errors): void
    {
        if ($errors->hasAny() && ! $this->isInteractive()) {
            if (! $this->wantsJson()) {
                throw new RuntimeException($errors);
            }

            $this->line($errors->toJson());

            exit(self::FAILURE);
        }
    }
}

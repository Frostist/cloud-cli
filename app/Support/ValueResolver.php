<?php

namespace App\Support;

use App\Dto\ValidationErrors;
use RuntimeException;

class ValueResolver
{
    protected $fromInputCallback = null;

    protected $nonInteractivelyCallback = null;

    protected ?ValidationErrors $errors = null;

    public function __construct(
        protected string $argumentName,
        protected bool $isInteractive,
        protected ?string $value = null,
        protected string $resolveFromType = 'argument',
    ) {
        //
    }

    public function fromInput(callable $input): self
    {
        $this->fromInputCallback = $input;

        return $this;
    }

    public function nonInteractively(callable $callback): self
    {
        $this->nonInteractivelyCallback = $callback;

        return $this;
    }

    public function value(): ?string
    {
        $this->errors ??= new ValidationErrors;

        if ($this->value && ! $this->errors->has($this->argumentName)) {
            return $this->value;
        }

        if ($this->isInteractive && (! $this->value || $this->errors->has($this->argumentName))) {
            return ($this->fromInputCallback)($this->value);
        }

        if ($this->value) {
            return $this->value;
        }

        if ($this->isInteractive) {
            return null;
        }

        if ($this->nonInteractivelyCallback && $result = ($this->nonInteractivelyCallback)($this->value)) {
            return $result;
        }

        $message = match ($this->resolveFromType) {
            'option' => "{$this->argumentName} is required. Provide --{$this->argumentName} option.",
            default => "{$this->argumentName} is required. Provide {$this->argumentName} argument.",
        };

        throw new RuntimeException($message);
    }

    public function errors(ValidationErrors $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}

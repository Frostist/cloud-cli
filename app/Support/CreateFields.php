<?php

namespace App\Support;

use App\Dto\ValidationErrors;

class CreateFields
{
    /**
     * @var array<string, ValueResolver>
     */
    protected array $fields = [];

    protected array $options = [];

    protected array $arguments = [];

    protected ValidationErrors $errors;

    protected bool $isInteractive;

    /**
     * @param  callable(ValueResolver): ValueResolver  $resolver
     */
    public function add(string $key, callable $resolver): ValueResolver
    {
        $this->fields[$key] ??= new ValueResolver(
            $key,
            $this->isInteractive,
            $this->options[$key] ?? $this->arguments[$key] ?? null,
            array_key_exists($key, $this->options) ? 'option' : 'argument',
        );

        $result = $resolver($this->fields[$key])->errors($this->errors);
        $result->retrieve();

        return $result;
    }

    public function isInteractive(bool $isInteractive): self
    {
        $this->isInteractive = $isInteractive;

        return $this;
    }

    public function errors(ValidationErrors $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function options($options): self
    {
        $this->options = $options;

        return $this;
    }

    public function arguments($arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->fields)) {
            return $default;
        }

        return $this->fields[$key]?->value();
    }

    public function all(): array
    {
        return collect($this->fields)->mapWithKeys(fn (ValueResolver $field) => [
            $field->key() => $field->value(),
        ])->toArray();
    }

    public function clear(): self
    {
        $this->fields = [];

        return $this;
    }
}

<?php

namespace App\Resolvers;

use App\Client\Connector;
use App\LocalConfig;
use RuntimeException;

abstract class Resolver
{
    protected bool $displayResolved = true;

    protected bool $isInteractive = true;

    public function __construct(
        protected Connector $client,
        protected LocalConfig $localConfig,
    ) {
        //
    }

    public function shouldDisplayResolved(bool $displayResolved = true): self
    {
        $this->displayResolved = $displayResolved;

        return $this;
    }

    public function isInteractive(bool $isInteractive = true): self
    {
        $this->isInteractive = $isInteractive;

        return $this;
    }

    protected function ensureInteractive(string $message): void
    {
        if (! $this->isInteractive) {
            throw new RuntimeException($message);
        }
    }

    protected function displayResolved(string $label, string $answer): void
    {
        if ($this->displayResolved) {
            answered(label: $label, answer: $answer);
        }
    }
}

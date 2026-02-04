<?php

namespace App\Prompts;

use App\Dto\EnvironmentLog;
use App\Support\KeyPressListener;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Support\Sleep;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Themes\Default\Concerns\InteractsWithStrings;
use RuntimeException;
use Throwable;

class EnvironmentLogsPrompt extends Prompt
{
    use Colors;
    use InteractsWithStrings;

    public int $count = 0;

    public int $interval = 75;

    public int $checkEvery = 3;

    public ?CarbonImmutable $lastCheck = null;

    public ?CarbonImmutable $loopStartedAt = null;

    public bool $fade = true;

    /**
     * @param  array<int, EnvironmentLog>  $logs
     * @param  Closure(string $from, string $to): array<int, EnvironmentLog>|null  $fetchLogs
     */
    public function __construct(
        public array $logs,
        public bool $live = false,
        protected ?Closure $fetchLogs = null,
        protected ?string $from = null,
        protected ?string $to = null,
    ) {
        //
    }

    public function display(): void
    {
        $this->capturePreviousNewLines();

        if (! $this->live) {
            $this->state = 'submit';
            $this->render();

            return;
        }

        $this->hideCursor();

        try {
            static::terminal()->setTty('-icanon -isig -echo');
        } catch (Throwable $e) {
            //
        }

        $keyPressListener = KeyPressListener::for($this)->listenForQuit();

        static::output()->write($this->renderTheme());

        while (true) {
            if ($this->loopStartedAt === null) {
                $this->loopStartedAt = CarbonImmutable::now();
            }

            static::writeDirectly($this->renderTheme());

            $this->count++;

            $now = CarbonImmutable::now();
            $shouldFetch = ($this->lastCheck === null && $this->loopStartedAt->diffInSeconds($now) >= $this->checkEvery)
                || ($this->lastCheck !== null && $this->lastCheck->diffInSeconds($now) >= $this->checkEvery);

            if ($shouldFetch && $this->fetchLogs && $this->from !== null && $this->to !== null) {
                $newLogs = ($this->fetchLogs)($this->from, $this->to);
                $this->logs = $newLogs;
                $this->from = $this->to;
                $this->to = $now->toIso8601String();
                $this->lastCheck = $now;
            }

            $keyPressListener->once();

            Sleep::for(CarbonInterval::milliseconds($this->interval));
        }
    }

    public function prompt(): never
    {
        throw new RuntimeException('EnvironmentLogsPrompt cannot be prompted.');
    }

    public function value(): bool
    {
        return true;
    }
}

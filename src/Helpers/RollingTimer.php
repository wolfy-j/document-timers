<?php

namespace App\Helpers;

use App\DTO\Queue;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Temporal\Workflow;

use function React\Promise\resolve;

class RollingTimer
{
    private \DateTimeInterface $last;
    private ?PromiseInterface $timer = null;
    private ?Deferred $ready = null;

    public function __construct(
        readonly private int $waitSeconds,
    ) {
        $this->last = Workflow::now();
    }

    public function touch(): void
    {
        $this->last = Workflow::now();
    }

    public function wait(Queue $queue, int $size): PromiseInterface
    {
        $this->ready ??= new Deferred();
        if ($this->timer === null) {
            $this->timer = Workflow::timer($this->waitSeconds);
            $this->timer->then($this->tick(...)); // unlocks current $this->ready
        }

        return Workflow::await(
            fn() => $queue->count() >= $size,
            $this->ready->promise(),
        )->then($this->reset(...));
    }

    private function tick(): void
    {
        $this->timer = null; // old timer gone

        if ($this->ready === null) {
            return;
        }

        // how long time passed since last event
        $passed = Workflow::now()->getTimestamp() - $this->last->getTimestamp();

        if ($passed < $this->waitSeconds) { // we captured recent event so the pipe is still alive
            $this->timer = Workflow::timer($this->waitSeconds);
            $this->timer->then($this->tick(...));
            return;
        }

        $this->ready->resolve(true);
        $this->ready = null;
    }

    private function reset(): void
    {
        $this->ready?->reject();
        $this->ready = null;
    }
}

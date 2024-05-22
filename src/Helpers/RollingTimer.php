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
        if ($queue->count() !== 0) {
            // we are starting with non-empty queue, process it immediately
            return resolve(true);
        }

        $this->ready = new Deferred();
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

        // recent event, do we want to wait extra?
        if ($passed < $this->waitSeconds) {
            $remaining = $this->waitSeconds - $passed;

            // the next tick is more than 40% away, let's wait
            if ($remaining / $this->waitSeconds > 0.4 && $remaining > 1) {
                $this->timer = Workflow::timer($remaining);
                $this->timer->then($this->tick(...));
                return;
            }
        }

        $this->ready->resolve(true);
    }

    private function reset(): void
    {
        $this->ready?->reject();
        $this->ready = null;
    }
}

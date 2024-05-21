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

        if ($this->ready === null) {
            $this->ready = new Deferred();
            $timer = Workflow::timer($this->waitSeconds);
            $timer->then($this->tick(...));
        }

        return Workflow::await(
            fn() => $queue->count() >= $size,
            $this->ready->promise(),
        )->then($this->clean(...));
    }

    public function tick(): void
    {
        // how long time passed since last event
        $lastEvent = Workflow::now()->getTimestamp() - $this->last->getTimestamp();

        if ($lastEvent < $this->waitSeconds) {
            $this->timer = Workflow::timer($this->waitSeconds - $lastEvent);
            $this->timer->then($this->tick(...));
            return;
        }

        $this->ready->resolve(true);
    }

    private function clean(): void
    {
        $this->timer?->cancel();
        $this->ready?->reject();

        $this->timer = null;
        $this->ready = null;
    }
}

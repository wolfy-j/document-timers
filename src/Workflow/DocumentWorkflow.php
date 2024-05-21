<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Activity\ProcessActivity;
use App\DTO\Queue;
use App\Helpers\RollingTimer;
use Temporal\Activity\ActivityOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;

#[Workflow\WorkflowInterface]
class DocumentWorkflow
{
    private ProcessActivity|ActivityProxy $process;
    private Queue $queue;
    private RollingTimer $timer;

    public function __construct()
    {
        $this->queue = new Queue();
        $this->timer = new RollingTimer(5);

        $this->process = Workflow::newActivityStub(
            ProcessActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(5)
                ->withTaskQueue('demo_workflow'),
        );
    }

    #[Workflow\SignalMethod]
    public function capture(Queue $events): void
    {
        $this->queue = $this->queue->merge($events);
        $this->timer->touch(); // indicating fresh data
    }

    #[Workflow\WorkflowMethod(name: "document.events")]
    public function run(string $document_id, ?Queue $queue = null): \Generator
    {
        if ($queue !== null) {
            // we want to make sure we captured previous and current events
            $this->queue = $queue->merge($this->queue);
        }

        while (true) {
            // wait for more events vs timer
            yield $this->timer->waitBatch(fn() => $this->queue->count() >= 25);
            yield $this->process->queue($document_id, $this->queue->flush());

            // wait for more events to come, otherwise exit
            $ok = yield Workflow::awaitWithTimeout(10, fn() => $this->queue->count() > 0);
            if (!$ok && $this->queue->count() === 0) {
                break;
            }

            if (Workflow::getInfo()->historyLength > 500) {
                break;
            }
        }

        if ($this->queue->count()) {
            // restart as new workflow
            yield Workflow::continueAsNew(
                'document.events',
                [$document_id, $this->queue],
                Workflow\ContinueAsNewOptions::new()
                    ->withTaskQueue('demo_workflow'),
            );
        }
    }
}
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
    private RollingTimer $waiter;

    public function __construct()
    {
        $this->queue = new Queue();
        $this->waiter = new RollingTimer(5);

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
        $this->queue = $this->queue->mergeWithoutDuplicates($events);
        $this->waiter->touch(); // indicating fresh data
    }

    #[Workflow\WorkflowMethod(name: "document.events")]
    public function run(string $document_id, ?Queue $queue = null): \Generator
    {
        if ($queue !== null) {
            // we want to make sure we captured previous and current events
            $this->queue = $queue->merge($this->queue);
        }

        while (true) {
            // wait for timer or queue to fill up
            yield $this->waiter->wait($this->queue, size: 8);

            // no batches to wait for, exiting
            if ($this->queue->count() === 0) {
                break;
            }

            // processing our queue
            yield $this->process->queue($document_id, $this->queue->flush());

            // our workflow is too large, let's continue as new
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

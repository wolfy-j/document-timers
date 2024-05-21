<?php

namespace App\Helpers;

use App\DTO\Event;
use App\DTO\Queue;
use App\Workflow\DocumentWorkflow;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\IdReusePolicy;

class Pusher
{
    public function __construct(
        private WorkflowClientInterface $wfClient,
    ) {
    }

    public function push(string $document_id, Event ...$event): void
    {
        $wf = $this->wfClient->newWorkflowStub(
            class: DocumentWorkflow::class,
            options: WorkflowOptions::new()
                ->withTaskQueue('demo_workflow')
                ->withWorkflowId($document_id)
                ->withWorkflowIdReusePolicy(IdReusePolicy::AllowDuplicate),
        );

        $this->wfClient->startWithSignal(
            workflow: $wf,
            signal: 'capture',
            signalArgs: [new Queue($event)],
            startArgs: [$document_id],
        );
    }
}

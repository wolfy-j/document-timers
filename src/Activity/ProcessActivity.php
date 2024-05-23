<?php

namespace App\Activity;

use App\DTO\Queue;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'process.')]
class ProcessActivity
{
    #[ActivityMethod(name: 'handleEvents')]
    public function handleEvents(string $document_id, Queue $queue): void
    {
        // doing some processing
        file_put_contents('php://stderr', print_r([
            'document_id' => $document_id,
            'queue' => $queue,
        ], true));
    }
}

<?php

namespace App\DTO;

use Temporal\Internal\Marshaller\Meta\MarshalArray;

class Queue implements \Countable
{
    public function __construct(
        #[MarshalArray(of: Event::class)]
        private array $events = [],
    ) {
    }

    public function merge(Queue $queue): Queue
    {
        $this->events = array_merge($this->events, $queue->events);
        return $this;
    }

    public function mergeWithoutDuplicates(Queue $queue): Queue
    {
        foreach ($queue->events as $item) {
            foreach ($this->events as $iitem) {
                if ($iitem->entity === $item->entity && $iitem->action === $item->action) {
                    // duplicate
                    continue 2;
                }
            }

            $this->events[] = $item;
        }

        return $this;
    }

    public function count(): int
    {
        return count($this->events);
    }

    // Moves all events to new queue and flushes the current queue
    public function flush(): Queue
    {
        $queue = new Queue($this->events);
        $this->events = [];

        return $queue;
    }
}

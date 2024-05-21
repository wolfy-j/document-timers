<?php

namespace App\DTO;

class Event
{
    public function __construct(
        public string $entity,
        public string $action,
    ) {
    }
}

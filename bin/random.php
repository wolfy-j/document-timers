<?php

declare(strict_types=1);

use Temporal\Client;
use App\DTO\Event;
use App\Helpers\Pusher;

ini_set('display_errors', 'stderr');
require __DIR__ . '/../vendor/autoload.php';

$client = Client\WorkflowClient::create(Client\GRPC\ServiceClient::create('localhost:7233'));
$pusher = new Pusher($client);

function random_name(): string
{
    return sprintf('page.%d', rand(1, 10));
}

function random_action(): string
{
    $actions = ['created', 'updated', 'deleted'];
    return $actions[rand(0, 2)];
}

function rand_event(): Event
{
    return new Event(entity: random_name(), action: random_action());
}

for ($i = 0; $i < 100; $i++) {
    for ($y = 0; $y < 5; $y++) {
        $events = array_fill(0, rand(1, 5), rand_event());

        $pusher->push(
            'demo_doc_' . $y,
            ...$events,
        );
    }
}

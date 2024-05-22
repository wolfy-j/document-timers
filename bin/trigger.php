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

$pusher->push(
    'demo_doc',
    new Event(entity: random_name(), action: 'created'),
    new Event(entity: random_name(), action: 'updated'),
    new Event(entity: random_name(), action: 'created'),
    new Event(entity: random_name(), action: 'updated'),
    new Event(entity: random_name(), action: 'updated'),
);

$pusher->push(
    'demo_doc_2',
    new Event(entity: random_name(), action: 'created'),
    new Event(entity: random_name(), action: 'updated'),
    new Event(entity: random_name(), action: 'created'),
    new Event(entity: random_name(), action: 'updated'),
    new Event(entity: random_name(), action: 'updated'),
);

$pusher->push(
    'demo_doc_3',
    new Event(entity: random_name(), action: 'created'),
    new Event(entity: random_name(), action: 'updated'),
    new Event(entity: random_name(), action: 'created'),
    new Event(entity: random_name(), action: 'updated'),
    new Event(entity: random_name(), action: 'updated'),
);

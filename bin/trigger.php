<?php

declare(strict_types=1);

use Temporal\Client;
use App\DTO\Event;
use App\Helpers\Pusher;

ini_set('display_errors', 'stderr');
require __DIR__ . '/../vendor/autoload.php';

$client = Client\WorkflowClient::create(Client\GRPC\ServiceClient::create('localhost:7233'));
$pusher = new Pusher($client);


$pusher->push(
    'demo_doc',
    new Event(entity: 'page.1', action: 'created'),
    new Event(entity: 'page.1', action: 'updated'),
    new Event(entity: 'page.2', action: 'created'),
    new Event(entity: 'page.1', action: 'updated'),
    new Event(entity: 'page.1', action: 'updated'),

);

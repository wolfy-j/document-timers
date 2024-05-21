<?php

declare(strict_types=1);
ini_set('display_errors', 'stderr');
require __DIR__ . '/vendor/autoload.php';

$workerFactory = Temporal\WorkerFactory::create();

$worker = $workerFactory->newWorker(taskQueue: 'demo_workflow');

$worker->registerWorkflowTypes(App\Workflow\DocumentWorkflow::class);
$worker->registerActivity(
    App\Activity\ProcessActivity::class,
    fn() => new App\Activity\ProcessActivity(),
);

$workerFactory->run();

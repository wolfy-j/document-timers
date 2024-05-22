# Readme

A simple Workflow implementation with demonstration of rolling event queue on temporal (literally) workflow.

## How to run
Make sure to run Temporal server locally.  

```bash
$ composer install
$ ./vendor/bin/rr get
$ ./rr serve
```

To trigger workflow executions:

```bash
$ php ./bin/trigger.php
```

To run a random workload of 500 events:

```bash
$ php ./bin/random.php
```

> TIP: You can run workloads in parallel.

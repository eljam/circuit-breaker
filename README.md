Circuit Breaker
===============

Circuit breaker is heavily used in microservice architecture to find issues between microservices calls.

The main idea is to protect your code from making unnecessary call if the microservice you call is down.


# Features
- Automatic update. (i.e you don't have to manually add success or failure method like other library)
- Return result from the protected function
- Retry timeout
- Exclude some exceptions from being throwned, return null instead.
- Multiprocess updates handled with a cache library. Supports all cache provider from (doctrine cache library).
- Event powered

[![Build Status](https://img.shields.io/travis/eljam/circuit-breaker.svg?branch=master&style=flat-square)](https://travis-ci.org/eljam/circuit-breaker) [![Code Quality](https://img.shields.io/scrutinizer/g/eljam/circuit-breaker.svg?b=master&style=flat-square)](https://scrutinizer-ci.com/g/eljam/circuit-breaker/?branch=master) [![Code Coverage](https://img.shields.io/coveralls/eljam/circuit-breaker.svg?style=flat-square)](https://coveralls.io/r/eljam/circuit-breaker) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/dd1c1da1-d469-4113-80f3-874c9d1deffd/mini.png)](https://insight.sensiolabs.com/projects/dd1c1da1-d469-4113-80f3-874c9d1deffd) [![Latest Unstable Version](https://poser.pugx.org/eljam/circuit-breaker/v/unstable)](https://packagist.org/packages/eljam/circuit-breaker)
[![Latest Stable Version](https://poser.pugx.org/eljam/circuit-breaker/v/stable)](https://packagist.org/packages/eljam/circuit-breaker)


Full Example:

```php
<?php

use Doctrine\Common\Cache\FilesystemCache;
use Eljam\CircuitBreaker\Breaker;
use Eljam\CircuitBreaker\Event\CircuitEvents;
use Symfony\Component\EventDispatcher\Event;

require_once __DIR__.'/vendor/autoload.php';

$fileCache  = new FilesystemCache('./store', 'txt');

//Create a circuit for github api with a file cache and we want to exclude all exception.
$breaker = new Breaker('github_api', ['ignore_exceptions' => true], $fileCache);

$breaker->addListener(CircuitEvents::SUCCESS, function (Event $event) {
    $circuit = $event->getCircuit();
    echo "Success:".$circuit->getFailures()."\n";
});

$breaker->addListener(CircuitEvents::FAILURE, function (Event $event) {
    $circuit = $event->getCircuit();
    echo "Increment failure:".$circuit->getFailures()."\n";
});

$breaker->addListener(CircuitEvents::OPEN, function (Event $event) {
    $circuit = $event->getCircuit();
    echo sprintf("circuit %s is open \n", $circuit->getName());
});

$breaker->addListener(CircuitEvents::CLOSED, function (Event $event) {
    $circuit = $event->getCircuit();
    echo sprintf("circuit %s is closed \n", $circuit->getName());
});

$breaker->addListener(CircuitEvents::HALF_OPEN, function (Event $event) {
    $circuit = $event->getCircuit();
    echo sprintf("circuit %s is half-open \n", $circuit->getName());
});

$result = $breaker->protect(function () {
    throw new \Exception("An error as occured");
    // return 'ok';
});

```


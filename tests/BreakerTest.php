<?php

namespace Eljam\CircuitBreaker;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Eljam\CircuitBreaker\Event\CircuitEvent;
use Eljam\CircuitBreaker\Event\CircuitEvents;
use Eljam\CircuitBreaker\Exception\CircuitOpenException;
use Eljam\CircuitBreaker\Exception\CustomException;

/**
 * Class BreakerTest.
 */
class BreakerTest extends \PHPUnit\Framework\TestCase
{
    protected $dir;

    public function setUp(): void
    {
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'store';
    }

    /**
     * testMultiProcess.
     */
    public function testMultiProcess(): void
    {
        $fileCache = new FilesystemAdapter('test', 0, $this->dir);
        $breaker = new Breaker('github_api', ['ignore_exceptions' => true], $fileCache);
        $breaker2 = new Breaker('github_api', ['ignore_exceptions' => true], $fileCache);

        $breaker1FailureCount = 0;

        $breaker->addListener(CircuitEvents::FAILURE, function (CircuitEvent $event) use (&$breaker1FailureCount) {
            $breaker1FailureCount = $event->getCircuit()->getFailures();
        });

        $breaker2->addListener(CircuitEvents::FAILURE, function (CircuitEvent $event) use (&$breaker1FailureCount) {
            $this->assertEquals($breaker1FailureCount, $event->getCircuit()->getFailures());
        });

        $fn = function () {
            throw new CustomException("An error as occurred");
        };

        $breaker->protect($fn);

        $breaker2->protect($fn);
    }

    /**
     * testOpenBehavior.
     */
    public function testOpenBehavior(): void
    {
        $breaker = new Breaker(
            'exception breaker',
            ['exclude_exceptions' => [CustomException::class]]
        );

        $breaker->addListener(CircuitEvents::OPEN, function (CircuitEvent $event) {
            $this->assertInstanceOf(Circuit::class, $event->getCircuit());
        });

        $this->expectException(CircuitOpenException::class);

        $fn = function () {
            throw new CustomException("An error as occurred");
        };

        for ($i = 0; $i <= 5; $i++) {
            $breaker->protect($fn);
        }
    }

    /**
     * testHalfOpenBehavior.
     */
    public function testHalfOpenBehavior(): void
    {
        $breaker = new Breaker(
            'exception breaker',
            [
                'reset_timeout' => 1,
                'ignore_exceptions' => true,
            ]
        );

        $breaker->addListener(CircuitEvents::HALF_OPEN, function (CircuitEvent $event) {
            $this->assertInstanceOf(Circuit::class, $event->getCircuit());
        });

        $fn = function () {
            throw new CustomException("An error as occurred");
        };

        try {
            for ($i = 0; $i <= 5; $i++) {
                $breaker->protect($fn);
            }
        } catch (CircuitOpenException $e) {
            $this->assertSame(CircuitOpenException::class, get_class($e));
        }

        sleep(2);

        $fnPass = function () {
            return 'ok';
        };

        $breaker->protect($fnPass);
    }

    /**
     * testGetTheResult.
     */
    public function testGetTheResult(): void
    {
        $breaker = new Breaker('simple_echo');
        $hello = 'eljam';

        $fn = function () use ($hello) {
            return $hello;
        };

        $result = $breaker->protect($fn);

        $this->assertSame($hello, $result);
    }

    /**
     * testIgnoreException.
     */
    public function testIgnoreAllException(): void
    {
        $breaker = new Breaker(
            'simple_echo',
            ['ignore_exceptions' => true]
        );
        $hello = 'eljam';

        $fn = function () use ($hello) {
            throw new CustomException("An error as occurred");

            return $hello;
        };

        $result = $breaker->protect($fn);

        $this->assertNull($result);
    }

    /**
     * testThrowCustomException.
     */
    public function testThrowCustomException(): void
    {
        $breaker = new Breaker(
            'custom_exception'
        );
        $hello = 'eljam';

        $this->expectException(CustomException::class);

        $fn = function () use ($hello) {
            throw new CustomException("An error as occurred");

            return $hello;
        };

        $breaker->protect($fn);
    }

    public function testAllowedException(): void
    {
        $breaker = new Breaker(
            'allowed_exception',
            [
                'ignore_exceptions' => false,
                'allowed_exceptions' => [
                    CustomException::class,
                ],
            ]
        );

        $breaker1FailureCount = 0;
        $breaker->addListener(CircuitEvents::FAILURE, function (CircuitEvent $event) use (&$breaker1FailureCount) {
            $breaker1FailureCount = $event->getCircuit()->getFailures();
        });

        $fn = function () {
            throw new CustomException("An error as occurred");
        };

        try {
            $breaker->protect($fn);
        } catch (CustomException $e) {
            $this->assertInstanceOf(CustomException::class, $e);
        }
        $this->assertSame(0, $breaker1FailureCount);
    }

    public function tearDown(): void
    {
        @unlink($this->dir);
    }
}

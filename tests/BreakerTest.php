<?php

namespace Eljam\CircuitBreaker;

use Doctrine\Common\Cache\FilesystemCache;
use Eljam\CircuitBreaker\Circuit;
use Eljam\CircuitBreaker\Event\CircuitEvents;
use Eljam\CircuitBreaker\Exception\CircuitOpenException;
use Eljam\CircuitBreaker\Exception\CustomException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class BreakerTest.
 */
class BreakerTest extends \PHPUnit_Framework_TestCase
{
    protected $dir;

    public function setUp()
    {
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'store';
    }

    /**
     * testMultiProcess.
     */
    public function testMultiProcess()
    {
        $fileCache  = new FilesystemCache($this->dir, 'txt');
        $breaker = new Breaker('github_api', ['ignore_exceptions' => true], $fileCache);
        $breaker2 = new Breaker('github_api', ['ignore_exceptions' => true], $fileCache);

        $breaker1FailureCount = 0;

        $breaker->addListener(CircuitEvents::FAILURE, function (Event $event) use (&$breaker1FailureCount) {
            $breaker1FailureCount = $event->getCircuit()->getFailures();
        });

        $breaker2->addListener(CircuitEvents::FAILURE, function (Event $event) use (&$breaker1FailureCount) {
            $this->assertEquals($breaker1FailureCount, $event->getCircuit()->getFailures());
        });

        $fn = function () {
            throw new CustomException("An error as occured");
        };

        $breaker->protect($fn);

        $breaker2->protect($fn);

    }

    /**
     * testOpenBehavior.
     */
    public function testOpenBehavior()
    {
        $breaker = new Breaker(
            'exception breaker',
            ['exclude_exceptions' => ['Eljam\CircuitBreaker\Exception\CustomException']]
        );

        $breaker->addListener(CircuitEvents::OPEN, function (Event $event) {
            $this->assertInstanceOf('Eljam\CircuitBreaker\Circuit', $event->getCircuit());
        });

        $this->setExpectedException('Eljam\CircuitBreaker\Exception\CircuitOpenException');

        $fn = function () {
            throw new CustomException("An error as occured");
        };

        for ($i = 0; $i <= 5; $i++) {
            $breaker->protect($fn);
        }
    }

    /**
     * testHalfOpenBehavior.
     */
    public function testHalfOpenBehavior()
    {
        $breaker = new Breaker(
            'exception breaker',
            [
                'reset_timeout' => 1,
                'ignore_exceptions' => true,
            ]
        );

        $breaker->addListener(CircuitEvents::HALF_OPEN, function (Event $event) {
            $this->assertInstanceOf('Eljam\CircuitBreaker\Circuit', $event->getCircuit());
        });

        $fn = function () {
            throw new CustomException("An error as occured");
        };

        try {
            for ($i = 0; $i <= 5; $i++) {
                $breaker->protect($fn);
            }
        } catch (CircuitOpenException $e) {
            $this->assertSame('Eljam\CircuitBreaker\Exception\CircuitOpenException', get_class($e));
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
    public function testGetTheResult()
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
    public function testIgnoreAllException()
    {
        $breaker = new Breaker(
            'simple_echo',
            ['ignore_exceptions' => true]
        );
        $hello = 'eljam';

        $fn = function () use ($hello) {
            throw new CustomException("An error as occured");

            return $hello;
        };

        $result = $breaker->protect($fn);

        $this->assertNull($result);
    }

    /**
     * testThrowCustomException.
     */
    public function testThrowCustomException()
    {
        $breaker = new Breaker(
            'custom_exception'
        );
        $hello = 'eljam';

        $this->setExpectedException('Eljam\CircuitBreaker\Exception\CustomException');

        $fn = function () use ($hello) {
            throw new CustomException("An error as occured");

            return $hello;
        };

        $breaker->protect($fn);

        $this->assertInstanceOf('Eljam\CircuitBreaker\Exception\CustomException', $result);
    }

    public function tearDown()
    {
        @unlink($this->dir);
    }
}

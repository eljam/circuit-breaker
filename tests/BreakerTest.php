<?php

namespace Eljam\CircuitBreaker;

use Eljam\CircuitBreaker\Exception\CustomException;

/**
 * Class BreakerTest.
 */
class BreakerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * testOpenBehavior.
     */
    public function testOpenBehavior()
    {
        $breaker = new Breaker(
            'exception breaker',
            ['exclude_exceptions' => [CustomException::class]]
        );

        $this->setExpectedException('Eljam\CircuitBreaker\Exception\CircuitOpenException');

        $fn = function () {
            throw new CustomException("An error as occured");
        };

        for ($i = 0; $i <= 5; $i++) {
            $breaker->protect($fn);
        }
    }


    /**
     * testGetTheResult.
     */
    public function testGetTheResult()
    {
        $breaker = new Breaker('simple echo');
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
    public function testIgnoreException()
    {
        $breaker = new Breaker(
            'simple echo',
            ['exclude_exceptions' => [CustomException::class]]
        );
        $hello = 'eljam';

        $fn = function () use ($hello) {
            throw new CustomException("An error as occured");

            return $hello;
        };

        $result = $breaker->protect($fn);

        $this->assertNull($result);
    }
}

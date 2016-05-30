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
        $breaker = new Breaker('exception breaker', [CustomException::class]);

        $this->setExpectedException('Eljam\CircuitBreaker\Exception\CircuitOpenException');

        $fn = function () {
            throw new CustomException("An error as occured");
        };

        for ($i = 0; $i <= 5; $i++) {
            $breaker->protect($fn);
        }
    }
}

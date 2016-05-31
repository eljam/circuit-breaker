<?php

/*
 * This file is part of the circuit-breaker package
 *
 * Copyright (c) 2016 Guillaume Cavana
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Guillaume Cavana <guillaume.cavana@gmail.com>
 */

namespace Eljam\CircuitBreaker;

/**
 * Class Circuit.
 */
class Circuit
{
    const OPEN = 'open';
    const HALF_OPEN = 'half-open';
    const CLOSED = 'closed';

    protected $name;
    protected $failureTreshold;
    protected $resetTimeout;
    protected $state = self::CLOSED;
    protected $failureCount = 0;
    protected $lastFailtureTime;

    /**
     * Constructor.
     *
     * @param string $name
     * @param int    $failureTreshold
     * @param int    $resetTimeout
     */
    public function __construct($name, $failureTreshold = 5, $resetTimeout = 5)
    {
        $this->name = $name;
        $this->failureTreshold = $failureTreshold;
        $this->resetTimeout = $resetTimeout;
    }

    /**
     * isClosed.
     *
     * @return bool
     */
    public function isClosed()
    {
        return $this->state == self::CLOSED;
    }

    /**
     * isOpen.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->state == self::OPEN;
    }

    /**
     * isHalfOpen.
     *
     * @return bool
     */
    public function isHalfOpen()
    {
        return $this->state == self::HALF_OPEN;
    }

    /**
     * getName.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * setState.
     *
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * getState.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * getFailureTreshold.
     *
     * @return int
     */
    public function getFailureTreshold()
    {
        return $this->failureTreshold;
    }

    /**
     * getResetTimeout.
     *
     * @return int
     */
    public function getResetTimeout()
    {
        return $this->resetTimeout;
    }

    /**
     * getFailureCount.
     *
     * @return int
     */
    public function getFailureCount()
    {
        return (int) $this->failureCount;
    }

    /**
     * setFailureCount.
     *
     * @param int $count
     */
    public function setFailureCount($count)
    {
        $this->failureCount = $count;
    }

    /**
     * getLastFailtureTime.
     *
     * @return int
     */
    public function getLastFailtureTime()
    {
        return  $this->lastFailtureTime;
    }

    /**
     * setLastFailtureTime.
     *
     * @param int $time
     */
    public function setLastFailtureTime($time)
    {
        $this->lastFailtureTime = $time;
    }
}

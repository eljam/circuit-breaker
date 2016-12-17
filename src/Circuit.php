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
    protected $name;
    protected $failures = 0;
    protected $lastFailure;

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
     * getFailureCount.
     *
     * @return int
     */
    public function getFailures()
    {
        return (int) $this->failures;
    }

    /**
     * incrementFailure.
     */
    public function incrementFailure()
    {
        $this->failures += 1;
    }

    /**
     * resetFailure.
     */
    public function resetFailure()
    {
        $this->failures = 0;
    }

    /**
     * getLastFailure.
     *
     * @return int
     */
    public function getLastFailure()
    {
        return $this->lastFailure;
    }

    /**
     * setLastFailure.
     *
     * @param int $time
     */
    public function setLastFailure($time)
    {
        $this->lastFailure = $time;
    }
}

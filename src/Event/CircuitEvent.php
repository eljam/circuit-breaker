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

namespace Eljam\CircuitBreaker\Event;

use Eljam\CircuitBreaker\Circuit;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CircuitEvent.
 */
class CircuitEvent extends Event
{
    protected $circuit;

    /**
     * Constructor.
     *
     * @param Circuit $circuit
     */
    public function __construct(Circuit $circuit)
    {
        $this->circuit = $circuit;
    }

    /**
     * getCircuit.
     *
     * @return Circuit
     */
    public function getCircuit()
    {
        return $this->circuit;
    }
}

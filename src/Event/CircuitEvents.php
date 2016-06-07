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

/**
 * Class CircuitEvents.
 */
final class CircuitEvents
{
    const SUCCESS = 'circuit.success';
    const FAILURE = 'circuit.failure';
    const OPEN = 'circuit.open';
    const CLOSED = 'circuit.closed';
    const HALF_OPEN = 'circuit.half_open';
}

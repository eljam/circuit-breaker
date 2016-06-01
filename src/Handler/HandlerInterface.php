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

namespace Eljam\CircuitBreaker\Handler;

/**
 * Class HandlerInterface.
 */
interface HandlerInterface
{
    /**
     * isClosed.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    public function isClosed(Circuit $circuit);

    /**
     * isOpen.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    public function isOpen(Circuit $circuit);

    /**
     * isHalfOpen.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    public function isHalfOpen(Circuit $circuit);
}

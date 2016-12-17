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

use Eljam\CircuitBreaker\Circuit;

/**
 * Handler.
 */
class Handler
{
    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function isClosed(Circuit $circuit)
    {
        return $circuit->getFailures() < $this->config['max_failure'];
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen(Circuit $circuit)
    {
        return $circuit->getFailures() >= $this->config['max_failure'];
    }

    /**
     * {@inheritdoc}
     */
    public function isHalfOpen(Circuit $circuit)
    {
        return ($circuit->getFailures() >= $this->config['max_failure'])
            && (time() - $circuit->getLastFailure() > $this->config['reset_timeout']);
    }
}

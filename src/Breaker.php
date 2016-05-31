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

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Eljam\CircuitBreaker\Exception\CircuitOpenException;
use Eljam\CircuitBreaker\Util\Utils;

/**
 * Class Breaker.
 */
class Breaker
{
    /**
     * $circuit.
     *
     * @var Circuit
     */
    protected $circuit;

    /**
     * $cache.
     *
     * @var CacheProvider
     */
    protected $cache;

    /**
     * $excludeExceptions.
     *
     * @var array
     */
    protected $excludeExceptions = [];

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $cache
     * @param array  $excludeExceptions
     */
    public function __construct(
        $name,
        CacheProvider $cache = null,
        array $excludeExceptions = []
    ) {
        $name = Utils::snakeCase($name);
        $this->excludeExceptions = array_merge($this->excludeExceptions, $excludeExceptions);
        $this->cache = $cache !== null ? $cache : (new ArrayCache());

        if ($this->cache->contains($name)) {
            $this->circuit = $this->cache->fetch($name);
        } else {
            $this->circuit = new Circuit($name);
            $this->cache->save($name, $this->circuit);
        }
    }

    /**
     * protect.
     *
     * @param \Closure $closure
     * @throw  \Exception
     *
     * @return mixed
     */
    public function protect(\Closure $closure)
    {
        try {
            if ($this->circuit->isClosed() || $this->circuit->isHalfOpen()) {
                $result = $closure();
            } elseif ($this->circuit->isOpen()) {
                throw new CircuitOpenException();
            }
            $this->success();
        } catch (\Exception $e) {
            $result = $this->failure($e);
        }

        if ($result instanceof \Exception) {
            throw $e;
        }

        return $result;
    }

    /**
     * success.
     */
    protected function success()
    {
        $this->circuit->setFailureCount(0);
        $this->circuit->setState(Circuit::CLOSED);
        $this->cache->save($this->circuit->getName(), $this->circuit);
    }

    /**
     * trip.
     *
     * @param \Expcetion $e
     *
     * @return \Exception|void
     */
    protected function failure(\Exception $e)
    {
        $count = $this->circuit->getFailureCount();
        $this->circuit->setFailureCount($count += 1);

        //Theshold has been reached and timeout is passed
        //so we can try again
        if (($this->circuit->getFailureCount() >= $this->circuit->getFailureTreshold())
            && (time() - $this->circuit->getLastFailtureTime() > $this->circuit->getResetTimeout())) {
            $this->circuit->setState(Circuit::HALF_OPEN);
        //Theshold has been reached so we open the circuit
        } elseif ($this->circuit->getFailureCount() >= $this->circuit->getFailureTreshold()) {
            $this->circuit->setState(Circuit::OPEN);
        }

        $this->circuit->setLastFailtureTime(time());

        //We save the circuit state
        $this->cache->save($this->circuit->getName(), $this->circuit);

        if (!in_array(get_class($e), $this->excludeExceptions)) {
            return $e;
        }
    }
}

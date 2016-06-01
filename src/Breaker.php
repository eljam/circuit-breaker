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
use Doctrine\Common\Cache\Cache;
use Eljam\CircuitBreaker\Exception\CircuitOpenException;
use Eljam\CircuitBreaker\Handler\Handler;
use Eljam\CircuitBreaker\Handler\HandlerInterface;
use Eljam\CircuitBreaker\Util\Utils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Breaker.
 */
class Breaker
{
    /**
     * $name.
     *
     * @var string
     */
    protected $name;

    /**
     * $cache.
     *
     * @var Cache
     */
    protected $store;

    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * $handler.
     *
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * Constructor.
     *
     * @param string  $name
     * @param array   $config
     * @param Cache   $store
     * @param Handler $handler
     */
    public function __construct(
        $name,
        array $config = [],
        Cache $store = null,
        HandlerInterface $handler = null
    ) {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'max_failure' => 5,
            'reset_timeout' => 10,
            'exclude_exceptions' => [],
        ]);

        $resolver->setAllowedTypes('exclude_exceptions', 'array');
        $resolver->setAllowedTypes('max_failure', 'int');
        $resolver->setAllowedTypes('reset_timeout', 'int');

        $this->config = $resolver->resolve($config);
        $this->name = Utils::snakeCase($name);
        $this->store = $store !== null ? $store : (new ArrayCache());
        $this->handler = $handler !== null ? $handler($this->config) : new Handler($this->config);
        $this->circuit = $this->loadCircuit($this->name);
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
            $result = null;

            if ($this->isClosed($this->circuit) || $this->isHalfOpen($this->circuit)) {
                $result = $closure();
            } elseif ($this->isOpen($this->circuit)) {
                throw new CircuitOpenException();
            }
            $this->success($this->circuit);
        } catch (\Exception $e) {
            $this->failure($this->circuit);

            if (!in_array(get_class($e), $this->config['exclude_exceptions'])) {
                $result = $e;
            }
        }

        if ($result instanceof \Exception) {
            throw $e;
        }

        return $result;
    }

    /**
     * isClosed.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    private function isClosed($circuit)
    {
        return $this->handler->isClosed($circuit);
    }

    /**
     * isOpen.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    private function isOpen($circuit)
    {
        return $this->handler->isOpen($circuit);
    }

    /**
     * isHalfOpen.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    private function isHalfOpen($circuit)
    {
        return $this->handler->isHalfOpen($circuit);
    }

    /**
     * success.
     *
     * @param Circuit $circuit
     */
    private function success($circuit)
    {
        $circuit->resetFailture();

        $this->writeToStore($circuit);
    }

    /**
     * failure.
     *
     * @param Circuit $circuit
     */
    private function failure(Circuit $circuit)
    {
        $circuit->incrementFailure();
        $circuit->setLastFailure(time());

        $this->writeToStore($circuit);
    }

    /**
     * loadCircuit.
     *
     * @param string $name
     *
     * @return Circuit
     */
    private function loadCircuit($name)
    {
        if ($this->store->contains($name)) {
            $circuit = $this->store->fetch($name);
        } else {
            $circuit = new Circuit($name);
        }

        $this->writeToStore($circuit);

        return $circuit;
    }

    private function writeToStore(Circuit $circuit)
    {
        $this->store->save($circuit->getName(), $circuit);
    }
}

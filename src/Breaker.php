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
use Eljam\CircuitBreaker\Event\CircuitEvent;
use Eljam\CircuitBreaker\Event\CircuitEvents;
use Eljam\CircuitBreaker\Exception\CircuitOpenException;
use Eljam\CircuitBreaker\Handler\Handler;
use Eljam\CircuitBreaker\Handler\HandlerInterface;
use Eljam\CircuitBreaker\Util\Utils;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Breaker.
 */
class Breaker
{
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
     * $dispatcher.
     *
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * $circuit
     *
     * @var Circuit
     */
    protected $circuit;

    /**
     * Constructor.
     *
     * @param string                   $name
     * @param array                    $config
     * @param Cache                    $store
     * @param HandlerInterface         $handler
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        $name,
        array $config = [],
        Cache $store = null,
        HandlerInterface $handler = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'max_failure' => 5,
            'reset_timeout' => 5,
            'exclude_exceptions' => [],
            'ignore_exceptions' => false,
            'allowed_exceptions' => [],
        ]);

        $resolver->setAllowedTypes('exclude_exceptions', 'array');
        $resolver->setAllowedTypes('max_failure', 'int');
        $resolver->setAllowedTypes('reset_timeout', 'int');
        $resolver->setAllowedTypes('allowed_exceptions', 'array');

        $this->config = $resolver->resolve($config);
        $this->store = $store ?: new ArrayCache();
        $this->handler = $handler ?: new Handler($this->config);
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $name = Utils::snakeCase($name);
        $this->circuit = $this->loadCircuit($name);
    }

    /**
     * protect.
     *
     * @param \Closure $closure
     * @throws \Exception
     *
     * @return mixed
     */
    public function protect(\Closure $closure)
    {
        try {
            $result = null;
            $circuitOpenException = null;

            if ($this->isClosed($this->circuit) || $this->isHalfOpen($this->circuit)) {
                $result = $closure();
                $this->success($this->circuit);
            } elseif ($this->isOpen($this->circuit)) {
                $circuitOpenException = new CircuitOpenException();
            }
        } catch (\Exception $e) {
            if (!in_array(get_class($e), $this->config['allowed_exceptions'])) {
                $this->failure($this->circuit);
            }

            if (!$this->config['ignore_exceptions']) {
                if (!in_array(get_class($e), $this->config['exclude_exceptions'])) {
                    $result = $e;
                }
            }
        }

        // Throw circuit exception when it is opened
        if (null !== $circuitOpenException) {
            throw $circuitOpenException;
        }

        //Throw closure exception
        if ($result instanceof \Exception) {
            throw $result;
        }

        return $result;
    }

    /**
     * addListener.
     *
     * @param string         $eventName
     * @param \Closure|array $listener
     */
    public function addListener($eventName, $listener)
    {
        $this->dispatcher->addListener($eventName, $listener);
    }

    /**
     * isClosed.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    protected function isClosed(Circuit $circuit)
    {
        if ($this->handler->isClosed($circuit)) {
            $this->dispatcher->dispatch(CircuitEvents::CLOSED, (new CircuitEvent($circuit)));

            return true;
        }

        return false;
    }

    /**
     * isOpen.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    protected function isOpen(Circuit $circuit)
    {
        $open = false;
        if ($this->handler->isOpen($circuit)) {
            $this->dispatcher->dispatch(CircuitEvents::OPEN, (new CircuitEvent($circuit)));

            $open = true;
        }

        return $open;
    }

    /**
     * isHalfOpen.
     *
     * @param Circuit $circuit
     *
     * @return bool
     */
    protected function isHalfOpen(Circuit $circuit)
    {
        if ($this->handler->isHalfOpen($circuit)) {
            $this->dispatcher->dispatch(CircuitEvents::HALF_OPEN, (new CircuitEvent($circuit)));

            return true;
        }

        return false;
    }

    /**
     * success.
     *
     * @param Circuit $circuit
     */
    protected function success(Circuit $circuit)
    {
        $circuit->resetFailure();

        $this->dispatcher->dispatch(CircuitEvents::SUCCESS, (new CircuitEvent($circuit)));
        $this->writeToStore($circuit);
    }

    /**
     * failure.
     *
     * @param Circuit $circuit
     */
    protected function failure(Circuit $circuit)
    {
        $circuit->incrementFailure();
        $circuit->setLastFailure(time());

        $this->dispatcher->dispatch(CircuitEvents::FAILURE, (new CircuitEvent($circuit)));
        $this->writeToStore($circuit);
    }

    /**
     * loadCircuit.
     *
     * @param string $name
     *
     * @return Circuit
     */
    protected function loadCircuit($name)
    {
        $circuit = $this->store->fetch($name) ?: new Circuit($name);

        $this->writeToStore($circuit);

        return $circuit;
    }

    /**
     * @param Circuit $circuit
     */
    protected function writeToStore(Circuit $circuit)
    {
        $this->store->save($circuit->getName(), $circuit);
    }
}

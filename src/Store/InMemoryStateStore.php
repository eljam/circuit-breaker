<?php

namespace Eljam\CircuitBreaker\Store;

/**
 * Class InMemoryStateStore.
 */
class InMemoryStateStore
{
    protected $data;

    public function save($name, $circuitBreakerState)
    {
        $this->data[$name] = $circuitBreakerState;
    }

    public function laod($name)
    {
        return $this->data[$name];
    }
}

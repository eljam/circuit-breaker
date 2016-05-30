<?php

namespace Eljam\CircuitBreaker;

use Eljam\CircuitBreaker\Circuit;
use Eljam\CircuitBreaker\Exception\CircuitOpenException;

/**
 * Class Breaker.
 */
class Breaker
{
    protected $circuit;
    protected $excludeExceptions = [];

    /**
     * Constructor.
     * @param string $name
     * @param array  $excludeExceptions
     */
    public function __construct($name, array $excludeExceptions = [])
    {
        $this->excludeExceptions = array_merge($this->excludeExceptions, $excludeExceptions);
        $this->circuit = new Circuit();
        $this->circuit->name = $name;
    }

    /**
     * protect.
     * @param  \Closure $closure
     * @throw  \Exception
     * @return  mixed
     */
    public function protect(\Closure $closure)
    {
        try {
            if ($this->circuit->isClosed()) {
                $result = $closure();
            } elseif ($this->circuit->isOpen()) {
                throw new CircuitOpenException();
            }
        } catch (\Exception $e) {
            $result = $this->trip($e);
        }

        if ($result instanceof \Exception) {
            throw $e;
        }

        return $result;
    }

    /**
     * trip.
     * @param \Expcetion $e
     * @return \Exception|void
     */
    public function trip(\Exception $e)
    {
        $this->circuit->failureCount += 1;
        if ($this->circuit->failureCount >= $this->circuit->failureTreshold) {
            $this->circuit->state = Circuit::OPEN;
        }
        if (!in_array(get_class($e), $this->excludeExceptions)) {
            return $e;
        }
    }

    /**
     * getCircuit.
     * @return Circuit]
     */
    public function getCircuit()
    {
        return $this->circuit;
    }
}

<?php

namespace Eljam\CircuitBreaker;

/**
 * Class Circuit.
 */
class Circuit
{
    const OPEN = 'open';
    const CLOSED = 'closed';

    public $name;
    public $state = self::CLOSED;
    public $failureTreshold = 5;
    public $failureCount = 0;

    /**
     * isClosed.
     * @return boolean
     */
    public function isClosed()
    {
        return ($this->state == self::CLOSED);
    }

    /**
     * isOpen.
     * @return boolean
     */
    public function isOpen()
    {
        return ($this->state == self::OPEN);
    }
}

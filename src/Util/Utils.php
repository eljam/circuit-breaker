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

namespace Eljam\CircuitBreaker\Util;

/**
 * Class Utils.
 */
class Utils
{
    /**
     * snakeCase.
     *
     * @param string $word
     *
     * @return string
     */
    public static function snakeCase($word)
    {
        return strtolower(str_replace(' ', '_', $word));
    }
}

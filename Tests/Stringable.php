<?php

namespace ArturDoruch\Array_\Tests;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class Stringable
{
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}

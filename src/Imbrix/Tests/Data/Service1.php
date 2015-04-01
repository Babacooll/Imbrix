<?php

namespace Imbrix\Tests\Data;

/**
 * Class Service1
 *
 * @package Imbrix\Tests
 */
class Service1
{
    protected $string;

    /**
     * @param string $string
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }
}

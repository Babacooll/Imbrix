<?php

namespace Imbrix\Tests\Data;

/**
 * Class Service2
 *
 * @package Imbrix\Tests
 */
class Service2
{
    protected $service1;

    /**
     * @param Service1 $service1
     */
    public function __construct(Service1 $service1)
    {
        $this->service1 = $service1;
    }

    /**
     * @return Service1
     */
    public function getService1()
    {
        return $this->service1;
    }
}

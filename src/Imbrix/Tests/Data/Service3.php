<?php

namespace Imbrix\Tests\Data;

/**
 * Class Service3
 *
 * @package Imbrix\Tests\Data
 */
class Service3
{
    /** @var Service3 */
    protected $service3;

    /**
     * @param Service3 $service3
     */
    public function __construct(Service3 $service3)
    {
        $this->service3 = $service3;
    }
}

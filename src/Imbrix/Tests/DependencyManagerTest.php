<?php

namespace Imbrix\Tests;

use Imbrix\DependencyManager;
use Imbrix\Tests\Data\Service0;
use Imbrix\Tests\Data\Service1;
use Imbrix\Tests\Data\Service2;
use Imbrix\Tests\Data\Service3;

/**
 * Class DependencyManagerTest
 *
 * @package Imbrix\Tests
 */
class DependencyManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testParameter()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addParameter('test', 'value');

        $this->assertSame($dependencyManager->get('test'), 'value');
    }

    public function testService()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addService('service0', function () {
            return new Service0();
        });

        $this->assertEquals($dependencyManager->get('service0'), new Service0());
    }

    public function testSameServices()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addService('service0', function () {
            return new Service0();
        });

        $this->assertSame($dependencyManager->get('service0'), $dependencyManager->get('service0'));
    }

    public function testServiceParameterInjection()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addParameter('test', 'value');

        $dependencyManager->addService('service1', function ($test) {
            return new Service1($test);
        });

        $this->assertSame($dependencyManager->get('service1')->getString(), 'value');
    }

    public function testServiceServiceInjection()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addParameter('test', 'value');

        $dependencyManager->addService('service1', function ($test) {
            return new Service1($test);
        });

        $dependencyManager->addService('service2', function ($service1) {
            return new Service2($service1);
        });

        $this->assertEquals($dependencyManager->get('service2'), new Service2(new Service1('value')));
    }

    public function testServiceServiceInjectionInverseOrder()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addService('service2', function ($service1) {
            return new Service2($service1);
        });

        $dependencyManager->addService('service1', function ($test) {
            return new Service1($test);
        });

        $dependencyManager->addParameter('test', 'value');

        $this->assertEquals($dependencyManager->get('service2'), new Service2(new Service1('value')));
    }

    public function testUniqueGetService()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addService('service0', function () {
            return new Service0();
        });

        $this->assertEquals($dependencyManager->getUnique('service0'), $dependencyManager->getUnique('service0'));
        $this->assertNotSame($dependencyManager->getUnique('service0'), $dependencyManager->getUnique('service0'));
    }

    public function testUniqueNonRecursiveGetService()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addParameter('test', 'value');

        $dependencyManager->addService('service1', function ($test) {
            return new Service1($test);
        });

        $dependencyManager->addService('service2', function ($service1) {
            return new Service2($service1);
        });

        $this->assertEquals($dependencyManager->getUnique('service2'), $dependencyManager->getUnique('service2'));
        $this->assertNotSame($dependencyManager->getUnique('service2'), $dependencyManager->getUnique('service2'));
        $this->assertSame($dependencyManager->getUnique('service2')->getService1(), $dependencyManager->getUnique('service2')->getService1());
    }

    public function testUniqueRecursiveGetService()
    {
        $dependencyManager = new DependencyManager();

        $dependencyManager->addParameter('test', 'value');

        $dependencyManager->addService('service1', function ($test) {
            return new Service1($test);
        });

        $dependencyManager->addService('service2', function ($service1) {
            return new Service2($service1);
        });

        $this->assertEquals($dependencyManager->getUnique('service2', true), $dependencyManager->getUnique('service2', true));
        $this->assertNotSame($dependencyManager->getUnique('service2', true), $dependencyManager->getUnique('service2', true));
        $this->assertEquals($dependencyManager->getUnique('service2', true)->getService1(), $dependencyManager->getUnique('service2', true)->getService1());
        $this->assertNotSame($dependencyManager->getUnique('service2', true)->getService1(), $dependencyManager->getUnique('service2', true)->getService1());
    }

    public function testCircularRefenrece()
    {
        $dependencyManager = new DependencyManager(true);

        $this->setExpectedException('\InvalidArgumentException');

        $dependencyManager->addService('service3', function ($service3) {
            return new Service3($service3);
        });
    }
}

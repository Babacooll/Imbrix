<?php

namespace Imbrix;

/**
 * Class DependencyManager
 *
 * @package Imbrix
 */
class DependencyManager
{
    protected $services   = [];
    protected $parameters = [];
    protected $keys       = [];
    protected $treated    = [];

    /**
     * @param string   $serviceName
     * @param callable $serviceDefinition
     *
     * @throws \Exception
     *
     * @return self
     */
    public function addService($serviceName, \Closure $serviceDefinition)
    {
        $this->addKey($serviceName);

        $this->services[$serviceName] = $serviceDefinition;

        return $this;
    }

    /**
     * @param string $parameterName
     * @param string $parameterValue
     *
     * @return self
     */
    public function addParameter($parameterName, $parameterValue)
    {
        $this->addKey($parameterName);

        if (!is_string($parameterValue)) {
            throw new \InvalidArgumentException('Parameter definition must be string');
        }

        $this->parameters[$parameterName] = $parameterValue;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->treated[$name])) {
            return $this->treated[$name];
        }

        $treated = $this->getUnique($name);

        $this->treated[$name] = $treated;

        return $treated;
    }

    /**
     * @param string $name
     * @param bool   $uniqueDependencies
     *
     * @return mixed
     */
    public function getUnique($name, $uniqueDependencies = false)
    {
        if (isset($this->services[$name])) {
            $treated = $this->getService($name, $uniqueDependencies);
        } elseif (isset($this->parameters[$name])) {
            $treated = $this->getParameter($name);
        } else {
            throw new \InvalidArgumentException('This service/parameter does not exist');
        }

        return $treated;
    }

    /**
     * @param string $name
     */
    public function remove($name)
    {
        if (isset($this->treated[$name])) {
            unset($this->treated[$name]);
        }
        if (isset($this->services[$name])) {
            unset($this->services[$name]);
        }
        if (isset($this->parameters[$name])) {
            unset($this->parameters[$name]);
        }
    }

    /**
     * @return array
     */
    public function dumpAll()
    {
        return [
            'services'   => $this->dumpServices(),
            'parameters' => $this->dumpParameters()
        ];
    }

    /**
     * @return array
     */
    public function dumpServices()
    {
        $services = [];

        foreach ($this->services as $serviceName => $serviceDefinition) {
            $services[] = [
                'name'       => $serviceName,
                'class'      => get_class($serviceDefinition),
                'instancied' => isset($this->treated[$serviceName])
            ];
        }

        return $services;
    }

    /**
     * @return array
     */
    public function dumpParameters()
    {
        $parameters = [];

        foreach ($this->parameters as $parameterName => $parameterValue) {
            $parameters[] = [
                'name'       => $parameterName,
                'value'      => $parameterValue
            ];
        }

        return $parameters;
    }

    /**
     * @param string $keyName
     */
    protected function addKey($keyName)
    {
        if (isset($this->keys[$keyName])) {
            throw new \InvalidArgumentException('This service/parameter already exists');
        }

        $this->keys[$keyName] = true;
    }

    /**
     * @param string $serviceName
     * @param bool   $uniqueDependencies
     *
     * @return mixed
     */
    protected function getService($serviceName, $uniqueDependencies = false)
    {
        $service           = $this->services[$serviceName];

        $reflection        = new \ReflectionFunction($service);
        $arguments         = $reflection->getParameters();
        $serviceParameters = [];

        foreach ($arguments as $argument) {
            $serviceParameters[$argument->getName()] = !$uniqueDependencies ? $this->get($argument->getName()) : $this->getUnique($argument->getName(), true);
        }

        return call_user_func_array($this->services[$serviceName], $serviceParameters);
    }

    /**
     * @param string $parameterName
     *
     * @return string
     */
    protected function getParameter($parameterName)
    {
        return $this->parameters[$parameterName];
    }
}

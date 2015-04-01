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
     * Adds a service (closure) known by his serviceName to the services list after checking circular references
     *
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
        $this->checkCircularReferences($serviceName, $serviceDefinition);

        $this->services[$serviceName] = $serviceDefinition;

        return $this;
    }

    /**
     * Adds a parameter (value) known by his parameterName to the parameter list
     *
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
     * Get a service/parameter by his name, if it's a service and it has already
     * been summoned before, we will return its stored value
     *
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
     * Get a service/parameter by his name, if it's a service we will return a new summon of the closure
     * even if already summoned before
     *
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
     * Removes a service/parameter from the DependencyManager
     *
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
     * Dumps the services and parameters depencies mapping
     *
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
     * Dumps the services depencies mapping
     *
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
     * Dumps the parameters depencies mapping
     *
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
     * Adds the service/parameter name to the key list
     * (provides an easy way to get unique keys for both services and parameters)
     *
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
     * Returns a service by its name, this will always return a new summon of the service,
     * the $uniqueDependencies parameter allows you to get unique dependencies aswell
     *
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
     * Returns a parameter by its name
     *
     * @param string $parameterName
     *
     * @return string
     */
    protected function getParameter($parameterName)
    {
        return $this->parameters[$parameterName];
    }

    /**
     * Checks circular references in
     *
     * @param string   $serviceName
     * @param \Closure $serviceDefinition
     */
    protected function checkCircularReferences($serviceName, \Closure $serviceDefinition)
    {
        $reflection        = new \ReflectionFunction($serviceDefinition);
        $arguments         = $reflection->getParameters();

        foreach ($arguments as $argument) {
            if ($argument->getName() === $serviceName) {
                throw new \InvalidArgumentException('Circular exception detected');
            }
        }
    }
}

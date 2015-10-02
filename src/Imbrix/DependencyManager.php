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
    protected $circularReferencesCheck;

    /**
     * @param bool $circularReferencesCheck
     */
    public function __construct($circularReferencesCheck = false)
    {
        $this->circularReferencesCheck = $circularReferencesCheck;
    }

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

        if ($this->circularReferencesCheck) {
            $this->checkCircularReferences($serviceName, $serviceDefinition);
        }

        $this->services[$serviceName]['definition'] = $serviceDefinition;

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

        return $this->treated[$name] = $this->getUnique($name, [], false);
    }

    /**
     * Get a service/parameter by his name, if it's a service we will return a new summon of the closure
     * even if already summoned before
     *
     * @param string $name
     * @param array  $customParameters
     * @param bool   $uniqueDependencies
     *
     * @return mixed
     */
    public function getUnique($name, $customParameters = [], $uniqueDependencies = false)
    {
        if (isset($this->services[$name])) {
            return $this->getService($name, $customParameters, $uniqueDependencies);
        }

        if (isset($this->parameters[$name])) {
            return $this->getParameter($name);
        }

        throw new \InvalidArgumentException(sprintf('This service/parameter %s does not exist', $name));
    }

    /**
     * Removes a service/parameter from the DependencyManager
     *
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->treated[$name]);
        unset($this->services[$name]);
        unset($this->parameters[$name]);
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

        foreach ($this->services as $serviceName => $service) {
            $services[$serviceName] = $this->getServiceDump($serviceName);
        }

        return $services;
    }

    /**
     * @param string $serviceName
     *
     * @return array
     */
    public function getServiceDump($serviceName)
    {
        $this->assertServiceExist($serviceName);

        return [
            'class'      => get_class($this->services[$serviceName]['definition']),
            'arguments'  => $this->getRegisterServiceArguments($serviceName)
        ];
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
            $parameters[$parameterName] = [
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
            throw new \InvalidArgumentException(sprintf('This service/parameter %s already exists', $keyName));
        }

        $this->keys[$keyName] = true;
    }

    /**
     * Returns a service by its name, this will always return a new summon of the service,
     * the $uniqueDependencies parameter allows you to get unique dependencies aswell
     *
     * @param string $serviceName
     * @param array  $customParameters
     * @param bool   $uniqueDependencies
     *
     * @return mixed
     */
    protected function getService($serviceName, $customParameters = [], $uniqueDependencies = false)
    {
        $arguments = $this->getRegisterServiceArguments($serviceName);
        $serviceParameters = [];

        foreach ($arguments as $argument) {
            if (isset($customParameters[$argument])) {
                $serviceParameters[$argument] = $customParameters[$argument];
            } elseif ($uniqueDependencies) {
                $serviceParameters[$argument] = $this->getUnique($argument, [], true);
            } else {
                $serviceParameters[$argument] = $this->get($argument);
            }
        }

        return call_user_func_array($this->services[$serviceName]['definition'], $serviceParameters);
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
     * @param string $name
     */
    protected function checkCircularReferences($name, \Closure $definition)
    {
        $arguments  = $this->getServiceArgumentNames($definition);

        if (in_array($name, $arguments, true)) {
            throw new \InvalidArgumentException('Circular exception detected');
        }
    }

    /**
     * @param string $serviceName
     *
     * @return array
     */
    protected function getRegisterServiceArguments($serviceName)
    {
        $this->assertServiceExist($serviceName);

        return isset($this->services[$serviceName]['arguments'])
            ? $this->services[$serviceName]['arguments']
            : $this->services[$serviceName]['arguments'] = $this->getServiceArgumentNames($this->services[$serviceName]['definition']);
    }

    protected function getServiceArgumentNames(\Closure $definition)
    {
        $reflection = new \ReflectionFunction($definition);
        $arguments = $reflection->getParameters();
        $names = [];

        foreach ($arguments as $argument) {
            $names[] = $argument->getName();
        }

        return $names;
    }

    protected function assertServiceExist($name)
    {
        if (empty($this->services[$name])) {
            throw new \InvalidArgumentException(sprintf('Service "%s" absent', $name));
        }
    }
}

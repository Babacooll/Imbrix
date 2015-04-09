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

        $treated = $this->getUnique($name, false);

        $this->treated[$name] = $treated;

        return $treated;
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
            $treated = $this->getService($name, $customParameters, $uniqueDependencies);
        } elseif (isset($this->parameters[$name])) {
            $treated = $this->getParameter($name);
        } else {
            throw new \InvalidArgumentException(sprintf('This service/parameter %s does not exist', $name));
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
        if (!isset($this->services[$serviceName]['arguments'])) {
            $this->getServiceArguments($serviceName);
        }

        return [
            'class'      => get_class($this->services[$serviceName]['definition']),
            'arguments'  => $this->services[$serviceName]['arguments']
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
        $arguments = $this->getServiceArguments($serviceName);
        $serviceParameters = [];
        $args = func_get_args()[1];

        foreach ($arguments as $argument) {
            if (isset($args[$argument])) {
                $serviceParameters[$argument] = $args[$argument];
            } elseif ($uniqueDependencies) {
                $serviceParameters[$argument] = $this->getUnique($argument, true);
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
     * @param string $serviceName
     */
    protected function checkCircularReferences($serviceName)
    {
        $arguments  = $this->getServiceArguments($serviceName);

        foreach ($arguments as $argumentKey) {
            if ($argumentKey === $serviceName) {
                throw new \InvalidArgumentException('Circular exception detected');
            }
        }
    }

    /**
     * @param string $serviceName
     *
     * @return array
     */
    protected function getServiceArguments($serviceName)
    {
        if (!isset($this->services[$serviceName])) {
            throw new \InvalidArgumentException('Circular exception detected');
        }

        if (!isset($this->services[$serviceName]['arguments'])) {
            $reflection = new \ReflectionFunction($this->services[$serviceName]['definition']);
            $arguments = $reflection->getParameters();
            $this->services[$serviceName]['arguments'] = [];

            foreach ($arguments as $argument) {
                $this->services[$serviceName]['arguments'][] = $argument->getName();
            }
        }

        return $this->services[$serviceName]['arguments'];
    }
}

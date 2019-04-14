<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace TASoft\Service;

use TASoft\Collection\AbstractCollection;
use TASoft\Collection\Mapping\CallbackMapper;
use TASoft\Collection\Mapping\CollectionMapping;
use TASoft\Collection\Mapping\RecursiveCallbackMapper;
use TASoft\PHP\SignatureService;
use TASoft\Service\Config\AbstractFileConfiguration;
use TASoft\Service\Container\CallbackContainer;
use TASoft\Service\Container\ConfiguredServiceContainer;
use TASoft\Service\Container\ContainerInterface;
use TASoft\Service\Container\ServiceAwareContainerInterface;
use TASoft\Service\Container\ServicePromise;
use TASoft\Service\Container\StaticContainer;
use TASoft\Service\Exception\BadConfigurationException;
use TASoft\Service\Exception\ServiceException;
use TASoft\Service\Exception\UnknownServiceException;

/**
 * The service manager reads from a configuration the registered services and instantiate them on demand.
 *
 * The specifier following the $ sign represents the requested service name.
 *
 * @package TASoft\Service
 */
class ServiceManager
{
    private static $serviceManager;
    private static $globalVariableName = "SERVICES";

    private $serviceData = [];
    private $parameters = [];
    private $serviceClassNames = [];

    private $replaceExistingServices = false;

    private $selfReferenceNames = [
        'serviceManager',
        "SERVICES"
    ];

    /**
     * Returns the service manager. The first call of this method should pass a service config info.
     * @param iterable|NULL $serviceConfig
     * @return ServiceManager
     */
    public static function generalServiceManager(Iterable $serviceConfig = NULL, $selfRefNames= []) {
        if(!static::$serviceManager) {
            if(is_null($serviceConfig))
                throw new ServiceException(sprintf("First call of %s must pass a service configuration", __METHOD__), 13);

            /** @var ServiceManager $man */
            $man = static::$serviceManager = new static($serviceConfig);
            $man->selfReferenceNames = array_merge($man->selfReferenceNames, $selfRefNames);

            if($n = self::getGlobalVariableName())
                $GLOBALS[$n] = $man;
        }
        return static::$serviceManager;
    }

    public static function rejectGeneralServiceManager() {
        static::$serviceManager = NULL;
        if($n = self::getGlobalVariableName())
            unset($GLOBALS[$n]);
    }

    public function __construct(Iterable $config = []) {
        $initializeOnLoad = [];

        foreach($config as $serviceName => $serviceConfig) {
            if(!is_iterable($serviceConfig)) {
                $e = new BadConfigurationException("Invalid service configuration. Require an array with service name as keys and service config as their values");
                $e->setConfiguration($serviceConfig);
                throw $e;
            }
            $container = new ConfiguredServiceContainer($serviceName, $serviceConfig, $this);

            if($serviceConfig[AbstractFileConfiguration::CONFIG_SERVICE_INIT_ON_LOAD_KEY] ?? false)
                $initializeOnLoad[] = $serviceName;

            $this->set($serviceName, $container);
        }

        foreach($initializeOnLoad as $serviceName)
            $this->get($serviceName);
    }

    /**
     * @return string
     */
    public static function getGlobalVariableName(): string
    {
        return self::$globalVariableName;
    }

    /**
     * @param string $globalVariableName
     */
    public static function setGlobalVariableName(string $globalVariableName): void
    {
        self::$globalVariableName = $globalVariableName;
    }

    /**
     * Returns a list with all available services
     * @return array
     */
    public function getAvailableServices() {
        return array_keys($this->serviceData);
    }

    /**
     * You may directly request a service instance. If it does not exist, the request returns NULL
     *
     *
     * @param $serviceName
     * @return object|null
     * @see ServiceManager::get()
     */
    public function __get($serviceName) {
        try {
            return $this->get($serviceName);
        } catch (ServiceException $e) {
        }
        return NULL;
    }


    /**
     * Returns the instance of a requested service
     * This method call fails if the service does not exist or something else went wrong during creating the service instance.
     *
     * @param string $serviceName
     * @return object|null
     * @throws UnknownServiceException
     */
    public function get($serviceName) {
        $container = $this->serviceData[$serviceName] ?? NULL;
        if($container) {
            return $container->getInstance();
        }

        if(in_array($serviceName, $this->getSelfReferenceNames()))
            return $this;

        $e = new UnknownServiceException("Service $serviceName is not registered", E_USER_ERROR);
        $e->setServiceName($serviceName);
        throw $e;
    }

    /**
     * Dynamically register a new service.
     * Directly registered services ignore all warnings during registration.
     *
     * @param $serviceName
     * @param $object
     * @see ServiceManager::set()
     */
    public function __set($serviceName, $object) {
        @$this->set($serviceName, $object);
    }

    /**
     * Sets a service
     *
     * Accepted are:
     * - Objects that implement ContainerInterface, their getInstance method call will be the service instance
     * - A callback (note! Objects implementing __invoke are also callbacks!) to obtain the service instance
     * - An array to load configured service
     * - Any other object directly as service
     *
     * If replaceExistingServices() returns false, this method call fails with an already registered service.
     *
     *
     * @param string $serviceName
     * @param object|callable|ContainerInterface $object
     * @throws ServiceException
     * @see ServiceManager::replaceExistingServices()
     * @see ServiceManager::__get()
     */
    public function set(string $serviceName, $object) {
        if(isset($this->serviceData[$serviceName]) || in_array($serviceName, $this->getSelfReferenceNames())) {
            if($this->replaceExistingServices()) {
                trigger_error("Service $serviceName is already registered", E_USER_NOTICE);
            } else {
                $e = new ServiceException("Service $serviceName is already registered");
                $e->setServiceName($serviceName);
                throw $e;
            }
        }


        if(is_callable($object)) {
            $object = new CallbackContainer($object);
        } elseif (is_array($object)) {
            $object = new ConfiguredServiceContainer($serviceName, $object, $this);
        } elseif(!is_object($object)) {
            throw new ServiceException("Service Manager only allows objects to be service instances!");
        } elseif(!($object instanceof ContainerInterface)) {
            $object = new StaticContainer($object);
        }

        $this->serviceData[$serviceName] = $object;
    }

    /**
     * Looks, if a service with the given name is available
     * @param string $serviceName
     * @return bool
     */
    public function serviceExists(string $serviceName): bool {
        return isset($this->serviceData[$serviceName]) || in_array($serviceName, $this->getSelfReferenceNames()) ? true : false;
    }

    /**
     * Looks, if a service with the given name is available and already is loaded.
     * @param string $serviceName
     * @return bool
     * @see ServiceManager::serviceExists()
     */
    public function isServiceLoaded(string $serviceName): bool {
        if($this->serviceExists($serviceName)) {
            if(in_array($serviceName, $this->getSelfReferenceNames()))
                return true;

            /** @var ContainerInterface $container */
            $container = $this->serviceData[$serviceName];
            return $container->isInstanceLoaded();
        }
        return false;
    }

    /**
     * Self references are names that reference the service manager itself.
     *
     * @return array
     */
    public function getSelfReferenceNames(): array
    {
        return $this->selfReferenceNames;
    }

    /**
     * @param array $selfReferenceNames
     */
    public function setSelfReferenceNames(array $selfReferenceNames): void
    {
        $this->selfReferenceNames = $selfReferenceNames;
    }

    /**
     * Parameters can be dynamic markers in the configuration that are resolved right before the service instance is required.
     * @example Config [
     *      AbstractFileConfiguration::SERVICE_CLASS            => MySQL::class,
     *      AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS   => [
     *          'host' => 'localhost',
     *          'dbname' => '%dataBaseName%'
     *      ]
     * ]
     * This configuration is valid and the %dataBaseName% parameter placeholder gets replaced by the parameter entry on service instance initialisation.
     *
     * @param string $paramName
     * @param null $paramValue
     */
    public function setParameter(string $paramName, $paramValue = NULL) {
        if($paramName) {
            if(is_null($paramValue) && isset($this->parameters[$paramName]))
                unset($this->parameters[$paramName]);
            else
                $this->parameters[$paramName] = $paramValue;
        }
    }

    /**
     * Returns the requested parameter value. If not set, the $contained argument is set to false, otherwise true
     *
     * @param string $name
     * @param bool $contained
     * @return mixed|null
     */
    public function getParameter(string $name, bool &$contained = NULL) {
        if(isset($this->parameters[$name])) {
            $contained = true;
            return $this->parameters[$name];
        }
        $contained = false;
        return NULL;
    }


    /**
     * Maps a given array into and resolves all markers /^\\$([a-zA-Z_][a-zA-Z_0-9]*)$/
     * with the expected service instance
     *
     * @param iterable $array
     * @param bool $recursive
     * @return iterable
     */
    public function mapArray( $array, bool $recursive = false): array {
        $handler = $this->_getMapValueHandler();
        $mapper = $recursive ? new RecursiveCallbackMapper($handler) : new CallbackMapper($handler);
        $mapping = new CollectionMapping($mapper);
        return $mapping->mapCollection($array);
    }

    /**
     * Maps parameters and service instances in value by the registered ones.
     * If value is an iterable, the mapping will iterate over it (if $recursive is set, also over children), otherwise it will map the passed value if needed.
     *
     * @param $value
     * @param bool $recursive
     * @return array|iterable|mixed
     */
    public function mapValue($value, bool $recursive = false) {
        if(is_iterable($value))
            return $this->mapArray($value, $recursive);
        return $this->_getMapValueHandler()(NULL, $value);
    }

    /**
     * Creates the replacement handler for parameters and service instances
     * @return \Closure
     */
    private function _getMapValueHandler() {
        return function($key, $value) {
            if(is_string($value)) {
                $value = preg_replace_callback("/%(.*?)%/", function($ms) {
                    $par = $this->getParameter($ms[1], $contained);
                    if($contained)
                        return $par;

                    trigger_error("Parameter $ms[0] not set", E_USER_WARNING);
                    return "%$ms[1]%";
                }, $value);
            }

            if(is_string($value) && preg_match("/^\\$([a-z_][a-z_0-9]*)$/i", $value, $ms)) {
                $name = $ms[1];
                if($this->serviceExists($name))
                    return $this->get($name);
            }
            return $value;
        };
    }

    /**
     * This method should be used to create service instances. It will check implementations and create it the requested manner.
     *
     * @param string $className
     * @param array|iterable|null $arguments
     * @param array|iterable|null $configuration
     * @return object|null
     */
    public function makeServiceInstance(string $className, $arguments = NULL, $configuration = NULL) {
        $instance = NULL;

        $implInterfaces = class_implements($className);
        if(in_array(ConstructorAwareServiceInterface::class, $implInterfaces)) {
            /** @var ConstructorAwareServiceInterface $className */
            if($args = $className::getConstructorArguments()) {
                $newArguments = [];

                foreach($args as $argName => $argValue) {
                    $newArguments[] = is_null($argValue) ? ($arguments[$argName] ?? NULL) : $argValue;
                }

                $arguments = $newArguments;
            }
        }

        if($arguments)
            $arguments = $this->mapArray( AbstractCollection::makeArray( $arguments ), true);

        if(in_array(StaticConstructorServiceInterface::class, $implInterfaces)) {
            $instance = new $className($arguments, $this);
        } elseif (in_array(DynamicConstructorServiceInterface::class, $implInterfaces)) {
            $sig = SignatureService::getSignatureService()->getMethodSignature($className, "__construct");
            $args = [];
            foreach($sig as $name => $type) {

            }
            $instance = new $className(...$args);
        } else {
            $instance = $arguments ? new $className(...$arguments) : new $className();
        }

        if($configuration && method_exists($instance, 'setConfiguration')) {
            $configuration = $this->mapArray( AbstractCollection::makeArray( $configuration ) );
            $instance->setConfiguration($configuration);
        }

        return $instance;
    }

    /**
     * @return bool
     */
    public function replaceExistingServices(): bool
    {
        return $this->replaceExistingServices;
    }

    /**
     * @param bool $replaceExistingServices
     */
    public function setReplaceExistingServices(bool $replaceExistingServices): void
    {
        $this->replaceExistingServices = $replaceExistingServices;
    }

    /**
     * Gets the class of a service instance
     *
     * @param string $serviceName
     * @param bool $forced      // If set, it will until load the service to get its class name
     * @return string|null
     */
    public function getServiceClass(string $serviceName, bool $forced = true): ?string {
        if(!isset($this->serviceClassNames[$serviceName])) {
            if($this->serviceExists($serviceName)) {
                /** @var ContainerInterface $container */
                $container = $this->serviceData[$serviceName];
                if($container->isInstanceLoaded()) {
                    $this->serviceClassNames[$serviceName] = get_class( $container->getInstance() );
                    goto finish;
                }

                if($container instanceof ServiceAwareContainerInterface) {
                    $this->serviceClassNames[$serviceName] = $container->getServiceClass();
                    goto finish;
                } elseif($container instanceof ConfiguredServiceContainer) {
                    $cfg = $container->getConfiguration();
                    // In case of a direct instance fonciguration, get this classname
                    if($cn = $cfg[AbstractFileConfiguration::SERVICE_CLASS] ?? NULL) {
                        $this->serviceClassNames[$serviceName] = $cn;
                        goto finish;

                    } elseif($cn = $cfg[AbstractFileConfiguration::CONFIG_SERVICE_TYPE_KEY] ?? NULL) {
                        // If the developer defined the class by configuration in case of container or file initialisation, use this.
                        $this->serviceClassNames[$serviceName] = $cn;
                        goto finish;
                    }
                }

                // If nothing worked before, finally load the instance
                if($forced)
                    $this->serviceClassNames[$serviceName] = get_class( $container->getInstance() );
            } else {
                // Mark as not existing
                $this->serviceClassNames[$serviceName] = false;
            }
        }

        finish:

        return $this->serviceClassNames[$serviceName] ?: NULL;
    }


    /**
     * Yields all services that match required service names or required class names.
     *
     * @param array $serviceNames
     * @param array $classNames
     * @param bool $includeSubclasses
     * @param bool $forceClassDetection
     * @return \Generator
     */
    public function yieldServices(array $serviceNames, array $classNames, bool $includeSubclasses = true, bool $forceClassDetection = true) {

        $matchClass = function($className) use ($includeSubclasses, $classNames) {
            if(in_array($className, $classNames))
                return true;
            if($includeSubclasses) {
                foreach($classNames as $cn) {
                    if(is_subclass_of($className, $cn))
                        return true;
                }
            }
            return false;
        };

        /**
         * @var string $serviceName
         * @var ContainerInterface $container
         */
        foreach($this->serviceData as $serviceName => $container) {
            if(in_array($serviceName, $serviceNames) || $matchClass($this->getServiceClass($serviceName, $forceClassDetection))) {
                yield $serviceName => new ServicePromise(function() use($container) {return $container->getInstance();});
            }
        }
    }

    public function getServices(array $serviceNames, array $classNames, bool $includeSubclasses = true, bool $forceClassDetection = true) {
        $services = [];
        foreach($this->yieldServices($serviceNames, $classNames, $includeSubclasses, $forceClassDetection) as $name => $service) {
            $services[$name] = $service;
        }
        return $services;
    }
}
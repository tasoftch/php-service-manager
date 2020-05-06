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

namespace TASoft\Service\Container;


use TASoft\Service\Config\AbstractFileConfiguration;
use TASoft\Service\ConfigurableTrait;
use TASoft\Service\Exception\BadConfigurationException;
use TASoft\Service\Exception\BadContainerException;
use TASoft\Service\Exception\FileNotFoundException;
use TASoft\Service\Exception\InvalidServiceException;
use TASoft\Service\Exception\ServiceException;
use TASoft\Service\ServiceManager;
use Throwable;

class ConfiguredServiceContainer extends AbstractContainer implements ServiceAwareContainerInterface
{
    use ConfigurableTrait;

    /** @var string */
    private $serviceName;
    /** @var ServiceManager */
    private $serviceManager;

    /** @var ContainerInterface If the configuration specified an initialisation by containers, the container instance is stored separately */
    private $containerInstance;

    /**
     * AbstractConfiguratedContainer constructor.
     * @param string $serviceName
     * @param array $serviceConfiguration
     * @param ServiceManager $serviceManager
     */
    public function __construct(string $serviceName, $serviceConfiguration = [], ServiceManager $serviceManager = NULL)
    {
        $this->serviceName = $serviceName;
        $this->serviceManager = $serviceManager ?: ServiceManager::generalServiceManager();
        $this->setConfiguration($serviceConfiguration);

        // Check, if the service will be instantiable
        if(
            !isset($serviceConfiguration[AbstractFileConfiguration::SERVICE_CLASS]) &&
            !isset($serviceConfiguration[AbstractFileConfiguration::SERVICE_FILE]) &&
            !isset($serviceConfiguration[AbstractFileConfiguration::SERVICE_CONTAINER])
        ) {
            $keys = implode("|", [AbstractFileConfiguration::SERVICE_CLASS, AbstractFileConfiguration::SERVICE_CONTAINER, AbstractFileConfiguration::SERVICE_FILE]);

            $e = new BadConfigurationException("Can not instantiate service $this->serviceName. Missing $keys key");
            $e->setConfiguration($serviceConfiguration);
            $e->setServiceName($this->serviceName);
            throw $e;
        }
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager(): ServiceManager
    {
        return $this->serviceManager;
    }


    /**
     * @inheritDoc
     */
    protected function loadInstance()
    {
        $config = $this->getConfiguration() ?? NULL;

        if($class = ($config[ AbstractFileConfiguration::SERVICE_CLASS ] ?? NULL)) {
            $this->loadInstanceFromClass($class);
            return true;
        }

        if($class = ($config[AbstractFileConfiguration::SERVICE_CONTAINER] ?? NULL)) {
            $this->loadInstanceFromContainer($class);
            return true;
        }

        if($class = ($config[AbstractFileConfiguration::SERVICE_FILE] ?? NULL)) {
            $this->loadInstanceFromFile($class);
            return true;
        }
        return false;
    }

    /**
     * Returns true, if the intermediate instance already is loaded
     * @return bool
     */
    protected function isIntermediateInstanceLoaded(): bool {
        return $this->containerInstance ? true : false;
    }

    /**
     * Loads the container from configuration that is able to create the final service instance.
     *
     * @param $containerClass
     * @return ContainerInterface
     */
    protected function loadIntermediateContainerInstance($containerClass): ContainerInterface {
        $arguments = $this->getConfiguration() [AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS] ?? NULL;
        $cfg = $this->getConfiguration() [AbstractFileConfiguration::SERVICE_INIT_CONFIGURATION] ?? NULL;

        try {
            /** @var ContainerInterface $instance */
            $instance = $this->serviceManager->makeServiceInstance($containerClass, $arguments, $cfg);
            if($instance instanceof ContainerInterface)
                return $instance;
            throw new ServiceException("Class $containerClass does not implement ". ContainerInterface::class, 893);
        } catch (Throwable $e) {
            $e = new BadContainerException($e->getMessage(), $e->getCode(), $e);
            $e->setServiceName($this->serviceName);
            $e->setContainer(NULL);
            throw $e;
        }
    }

    /**
     * Loads the service instance by creating an intermediate container and get its service instance
     *
     * @param string $containerClass
     * @throws ServiceException
     * @throws BadContainerException
     */
    protected function loadInstanceFromContainer($containerClass) {
        $this->containerInstance = $this->loadIntermediateContainerInstance($containerClass);

        try {
            $this->instance = $this->containerInstance->getInstance();
        } catch (BadConfigurationException $e) {
            $e = new ServiceException($e->getMessage(), $e->getCode(), $e);
            $e->setServiceName($this->serviceName);
            throw $e;
        }
    }

    /**
     * Loads the service instance directly by using a class name.
     * @param string $class
     * @throws ServiceException
     */
    protected function loadInstanceFromClass($class) {
        $arguments = $this->getConfiguration() [ AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS ] ?? NULL;
        $config = $this->getConfiguration() [ AbstractFileConfiguration::SERVICE_INIT_CONFIGURATION ] ?? NULL;

        $this->instance = $this->serviceManager->makeServiceInstance($class, $arguments, $config);
    }

    /**
     * Loads a service instance from return value from a required file
     * @param string $file
     * @throws InvalidServiceException
     * @throws FileNotFoundException
     */
    protected function loadInstanceFromFile($file) {
        if(file_exists($file)) {
            $arguments = $this->getConfiguration() [ AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS ] ?? NULL;

            $object = _context_less_require($file, $arguments, $this->serviceManager);
            if(is_object($object)) {
                $this->instance = $object;
                if(method_exists($object, 'setConfiguration') && ($cfg = $this->getConfiguration()[AbstractFileConfiguration::SERVICE_INIT_CONFIGURATION] ?? NULL))
                    $object->setConfiguration($cfg);
            }
            else {
                $e= new InvalidServiceException("Executation of file $file did not return an object");
                $e->setServiceName($this->serviceName);
                $e->setServiceObject($object);
                throw $e;
            }
        } else {
            $e = new FileNotFoundException("File $file does not exist");
            $e->setServiceName($this->serviceName);
            $e->setFilename($file);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getServiceClass(): string
    {
        if($this->isInstanceLoaded())
            return get_class( $this->instance );

        if($class = $this->getConfiguration()[ AbstractFileConfiguration::SERVICE_CLASS ] ?? NULL)
            return $class;

        if($class = $this->getConfiguration()[ AbstractFileConfiguration::CONFIG_SERVICE_TYPE_KEY ] ?? NULL)
            return $class;

        if(isset($this->getConfiguration()[AbstractFileConfiguration::SERVICE_CONTAINER]) && !$this->isIntermediateInstanceLoaded()) {
            $this->containerInstance = $this->loadIntermediateContainerInstance( $this->getConfiguration()[ AbstractFileConfiguration::SERVICE_CONTAINER ] );
        }

        if($this->containerInstance instanceof ServiceAwareContainerInterface)
            return $this->containerInstance->getServiceClass();

        $this->loadInstance();
        return is_object($this->instance) ? get_class($this->instance) : '';
    }
}


/**
 * @param string $file
 * @param array $ARGUMENTS
 * @param ServiceManager $SERVICE_MANAGER
 * @return mixed
 * @internal
 */
function _context_less_require($file, $ARGUMENTS, $SERVICE_MANAGER) {
    return require($file);
}
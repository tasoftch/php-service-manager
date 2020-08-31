<?php


namespace TASoft\Service;


use TASoft\Service\Container\ContainerInterface;
use TASoft\Service\Exception\ServiceException;
use TASoft\Service\Exception\UnknownServiceException;

interface ServiceManagerInterface
{
	/**
	 * Returns the service manager. The first call of this method should pass a service config info.
	 * @param iterable|NULL $serviceConfig
	 * @param array $selfRefNames   String names for referencing service manager
	 * @return ServiceManager
	 * @throws ServiceException
	 */
	public static function generalServiceManager(Iterable $serviceConfig = NULL, $selfRefNames= []);

	/**
	 * Rejects the current loaded service manager instance
	 *
	 * @return void
	 */
	public static function rejectGeneralServiceManager();


	/**
	 * Returns a list with all available services
	 * @return ServiceInterface[]
	 */
	public function getAvailableServices();


	/**
	 * Returns the instance of a requested service
	 * This method call fails if the service does not exist or something else went wrong during creating the service instance.
	 *
	 * @param string $serviceName
	 * @return ServiceInterface|null
	 * @throws UnknownServiceException
	 */
	public function get($serviceName);

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
	 * @param ServiceInterface|callable|ContainerInterface $object
	 * @throws ServiceException
	 * @see ServiceManager::replaceExistingServices()
	 * @see ServiceManager::__get()
	 */
	public function set(string $serviceName, $object);

	/**
	 * Looks, if a service with the given name is available
	 * @param string $serviceName
	 * @return bool
	 */
	public function serviceExists(string $serviceName): bool;

	/**
	 * Looks, if a service with the given name is available and already is loaded.
	 * @param string $serviceName
	 * @return bool
	 * @see ServiceManager::serviceExists()
	 */
	public function isServiceLoaded(string $serviceName): bool;


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
	public function setParameter(string $paramName, $paramValue = NULL);


	/**
	 * Returns the requested parameter value. If not set, the $contained argument is set to false, otherwise true
	 *
	 * @param string $name
	 * @param bool $contained
	 * @return mixed|null
	 */
	public function getParameter(string $name, bool &$contained = NULL);
}
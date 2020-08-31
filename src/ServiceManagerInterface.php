<?php
/*
 * MIT License
 *
 * Copyright (c) 2020 TASoft Applications
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

	/**
	 * Gets the filename where to store the installed service names.
	 *
	 * @return string
	 */
	public function getRegisteredServicePersistentFile(): string;

	/**
	 * Unregistering a service will invoke its probably defined uninstall method and removes from installed services.
	 *
	 * @param string $serviceName
	 */
	public function unregisterService(string $serviceName);
}
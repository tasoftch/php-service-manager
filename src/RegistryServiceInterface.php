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

/**
 * A service implemeting the registry interface gets informed about the very fist usage and the very final usage.
 * This may be helpful if a service might create data models at initialization and delete them if the service gets deleted finally.
 *
 * Please note that the service management will persistently store which services are already initialized.
 *
 * @package TASoft\Service
 */
interface RegistryServiceInterface extends ServiceInterface
{
	/**
	 * This method gets called the very first time the service gets initialized from configuration.
	 * This only happen once until the service gets unregistered explicit by the service manager.
	 *
	 * @param ServiceManagerInterface $serviceManager
	 */
	public function installService(ServiceManagerInterface $serviceManager);

	/**
	 * This method gets called if the service definitively should be removed from the application.
	 * All stuff the service required must be removed and undone by this method.
	 *
	 * @param ServiceManagerInterface $serviceManager
	 */
	public function uninstallService(ServiceManagerInterface $serviceManager);
}
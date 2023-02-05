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

use PHPUnit\Framework\TestCase;
use TASoft\Service\AbstractService;
use TASoft\Service\Config\AbstractFileConfiguration;
use TASoft\Service\RegistryServiceInterface;
use TASoft\Service\ServiceManager;
use TASoft\Service\ServiceManagerInterface;

/**
 * Class RegisteredServiceTest
 */
class ServiceRegisterTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		if(file_exists(ServiceManager::PARAM_INITIALIZED_FILE))
			unlink(ServiceManager::PARAM_INITIALIZED_FILE);

		ServiceManager::generalServiceManager([
			MockRegisterService::SERVICE_NAME => [
				AbstractFileConfiguration::SERVICE_CLASS => MockRegisterService::class
			]
		]);
	}

	public function testFirstRegisteredService() {
		$sm = ServiceManager::generalServiceManager();
		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertFalse(MockRegisterService::$didUninstall);

		$service = $sm->get(MockRegisterService::SERVICE_NAME);

		$this->assertTrue(MockRegisterService::$didInstall);
		$this->assertInstanceOf(MockRegisterService::class, $service);
	}

	/**
	 * @depends testFirstRegisteredService
	 */
	public function testSecondRegisteredService() {
		$sm = ServiceManager::generalServiceManager();
		$this->assertTrue(MockRegisterService::$didInstall);
		$this->assertFalse(MockRegisterService::$didUninstall);

		MockRegisterService::$didInstall = false;
		$service = $sm->get(MockRegisterService::SERVICE_NAME);
		$this->assertInstanceOf(MockRegisterService::class, $service);

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertFalse(MockRegisterService::$didUninstall);
	}

	/**
	 * @depends testSecondRegisteredService
	 */
	public function testDematerialize() {
		$this->assertFileNotExists(ServiceManager::PARAM_INITIALIZED_FILE);
		$sm = ServiceManager::generalServiceManager();
		ServiceManager::rejectGeneralServiceManager();
		$sm->__destruct();
		unset($sm);
		$this->assertFileExists(ServiceManager::PARAM_INITIALIZED_FILE);
	}

	public function testThirdRegisteredService() {
		$sm = ServiceManager::generalServiceManager([
			MockRegisterService::SERVICE_NAME => [
				AbstractFileConfiguration::SERVICE_CLASS => MockRegisterService::class
			]
		]);

		$service = $sm->get(MockRegisterService::SERVICE_NAME);
		$this->assertInstanceOf(MockRegisterService::class, $service);

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertFalse(MockRegisterService::$didUninstall);
	}

	public function testUninstallServiceLocally() {
		$sm = ServiceManager::generalServiceManager();

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertFalse(MockRegisterService::$didUninstall);

		$sm->unregisterService( MockRegisterService::SERVICE_NAME );

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertTrue(MockRegisterService::$didUninstall);
	}

	public function testUninstallServiceLocally2() {
		$sm = ServiceManager::generalServiceManager();

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertTrue(MockRegisterService::$didUninstall);

		$sm->unregisterService( MockRegisterService::SERVICE_NAME );

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertTrue(MockRegisterService::$didUninstall);
	}

	public function testUninstallServiceLocally3() {
		$sm = ServiceManager::generalServiceManager();

		$this->assertFalse(MockRegisterService::$didInstall);
		$this->assertTrue(MockRegisterService::$didUninstall);

		$sm->get( MockRegisterService::SERVICE_NAME );

		$this->assertTrue(MockRegisterService::$didInstall);
		$this->assertTrue(MockRegisterService::$didUninstall);
	}

	public function testUninstallServiceLocally4() {
		$sm = ServiceManager::generalServiceManager();
		$sm->unregisterService( MockRegisterService::SERVICE_NAME );

		$sm->__destruct();
		ServiceManager::rejectGeneralServiceManager();

		$sm = ServiceManager::generalServiceManager([
			MockRegisterService::SERVICE_NAME => [
				AbstractFileConfiguration::SERVICE_CLASS => MockRegisterService::class
			]
		]);

		MockRegisterService::$didUninstall=MockRegisterService::$didInstall=false;

		$sm->get( MockRegisterService::SERVICE_NAME );

		$this->assertTrue(MockRegisterService::$didInstall);
		$this->assertFalse(MockRegisterService::$didUninstall);
	}
}



class MockRegisterService extends AbstractService implements RegistryServiceInterface {
	const SERVICE_NAME = 'my-registry-service';

	public static $didInstall = false, $didUninstall = false;

	public function installService(ServiceManagerInterface $serviceManager)
	{
		self::$didInstall = true;
	}

	public function uninstallService(ServiceManagerInterface $serviceManager)
	{
		self::$didUninstall = true;
	}
}
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

/**
 * ServiceManagerTest.php
 * service-manager
 *
 * Created on 2019-04-07 00:57 by thomas
 */

use PHPUnit\Framework\TestCase;
use TASoft\Service\Config\AbstractFileConfiguration;
use TASoft\Service\ConstructorAwareServiceInterface;
use TASoft\Service\Container\AbstractContainer;
use TASoft\Service\Container\CallbackContainer;
use TASoft\Service\Container\ServiceAwareContainerInterface;
use TASoft\Service\Container\StaticContainer;
use TASoft\Service\Exception\BadConfigurationException;
use TASoft\Service\Exception\ServiceException;
use TASoft\Service\ServiceManager;
use TASoft\Service\StaticConstructorServiceInterface;

class ServiceManagerTest extends TestCase
{
    public function testGlobalService() {
        ServiceManager::setGlobalVariableName("_TEST");
        $this->assertFalse(isset( $GLOBALS["_TEST"] ));
        ServiceManager::generalServiceManager([]);

        $this->assertTrue(isset( $GLOBALS["_TEST"] ));
        global $_TEST;
        $this->assertInstanceOf(ServiceManager::class, $_TEST);
        $this->assertSame($_TEST, ServiceManager::generalServiceManager());


        $this->assertTrue(ServiceManager::generalServiceManager()->isServiceLoaded("serviceManager"));
        $this->assertFalse(ServiceManager::generalServiceManager()->isServiceLoaded("inexistentService"));

        ServiceManager::rejectGeneralServiceManager();

        $this->assertFalse(isset( $GLOBALS["_TEST"] ));
    }

    public function testInitialFailure() {
		$this->expectException(ServiceException::class);
        ServiceManager::rejectGeneralServiceManager();
        ServiceManager::generalServiceManager();
    }

    public function testFailedConfiguration() {
		$this->expectException(BadConfigurationException::class);
        try {
            $sm = new ServiceManager([
                AbstractFileConfiguration::SERVICE_CLASS => MockService::class
            ]);
        } catch (BadConfigurationException $exception) {
            $this->assertSame(MockService::class, $exception->getConfiguration());
            throw $exception;
        }
    }

    /**
     * @expectedException TASoft\Service\Exception\BadConfigurationException
     */
    public function testMissingConstructionMethodConfig() {
		$this->expectException(BadConfigurationException::class);
		try {
            $sm = new ServiceManager([
                'myService' => [
                    'irelevant' => MockService::class
                ]
            ]);
        } catch (BadConfigurationException $exception) {
            $this->assertSame([
                'irelevant' => MockService::class
            ], $exception->getConfiguration());
            throw $exception;
        }
    }

    public function testValidConfig() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CLASS => MockService::class
            ]
        ]);
        $this->assertTrue($sm->serviceExists("myService"));
        $this->assertFalse($sm->serviceExists("nonexisting"));

        $obj = $sm->myService;
        $this->assertInstanceOf(MockService::class, $obj);
        $this->assertSame($obj, $sm->get("myService"));

        $this->assertNull($sm->nonexistingService);
        $this->assertSame($sm, $sm->get("serviceManager"));
    }

    /**
     * @expectedException TASoft\Service\Exception\ServiceException
     */
    public function testSetExistingService() {
		$this->expectException(ServiceException::class);
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CLASS => MockService::class
            ]
        ]);

        $other = new MockService();
        $sm->set("myService", $other);
    }

    /**
     * @expectedException PHPUnit\Framework\Error\Notice
     */
    public function testSetExistingService2() {
		$this->expectException(\PHPUnit\Framework\Error\Notice::class);
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CLASS => MockService::class
            ]
        ]);

        $other = new MockService();
        $sm->setReplaceExistingServices(true);
        $sm->set("myService", $other);
    }

    public function testInitialLoadedService() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CLASS => MockService::class,
                AbstractFileConfiguration::CONFIG_SERVICE_INIT_ON_LOAD_KEY => true
            ]
        ]);
        $this->assertTrue($sm->isServiceLoaded("myService"));
    }

    public function testSetDifferentServiceKinds() {
        $sm = new ServiceManager([]);
        $this->assertCount(0, $sm->getAvailableServices());

        $sm->myService = $service1 = new stdClass();
        $sm->cbService = function() use (&$cbService) { return $cbService = new MockService(); };
        $sm->cntService = new StaticContainer($this);
        $sm->cfgService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockService::class
        ];

        $this->assertEquals(["myService", "cbService", "cntService", "cfgService"], $sm->getAvailableServices());

        $cbServiceTest = $sm->cbService;

        $this->assertSame($service1, $sm->myService);
        $this->assertSame($cbService, $cbServiceTest);
        $this->assertSame($this, $sm->cntService);
        $this->assertInstanceOf(MockService::class, $sm->cfgService);
    }

    /**
     * @expectedException TASoft\Service\Exception\ServiceException
     */
    public function testInvalidServiceInstance() {
		$this->expectException(ServiceException::class);
        $sm = new ServiceManager([]);
        $sm->testService = 16;
    }

    public function testArgumentedService() {
        $sm = new ServiceManager([]);

        $sm->otherService = $this;

        $sm->myService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockServiceArgumented::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                13,
                true,
                '$otherService'
            ]
        ];

        $this->assertFalse($sm->isServiceLoaded("myService"));
        $service = $sm->get("myService");

        $this->assertEquals([13, true, $this], $service->arguments);
    }

    public function testArgumentsAwareService() {
        $sm = new ServiceManager([]);

        $sm->testService = $this;

        $sm->myService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockServiceArgumentedAware::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                'nr' => 13,
                'bool' => true,
                '$otherService'
            ]
        ];

        $service = $sm->get("myService");
        $this->assertSame([17, $this, true], $service->arguments);
    }

    public function testStaticArgumentsService() {
        $sm = new ServiceManager([]);
        $sm->otherService = $this;

        $sm->myService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockStaticConstructionService::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                13,
                true,
                '$otherService'
            ]
        ];

        $service = $sm->get("myService");
        $this->assertSame([13, true, $this], $service->arguments);
        $this->assertSame($sm, $service->sm);
    }

    public function testConfigurableService() {
        $sm = new ServiceManager([]);
        $sm->otherService = $this;

        $sm->myService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockConfigService::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                13,
                true
            ],
            AbstractFileConfiguration::SERVICE_INIT_CONFIGURATION => [
                'reference' => '$otherService'
            ]
        ];

        $service = $sm->get("myService");
        $this->assertSame([13, true], $service->arguments);
        $this->assertSame(["reference" => $this], $service->config);
    }

    public function testParameteredService() {
        $sm = new ServiceManager([]);

        $sm->myService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockStaticConstructionService::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                13,
                true,
                '%test%',
                '%prefix%-file'
            ]
        ];

        $sm->setParameter("test", 188);
        $sm->setParameter("prefix", 'ta');

        $service = $sm->myService;
        $this->assertEquals([13, true, 188, 'ta-file'], $service->arguments);
    }

    public function testNonexistingParameteredService() {
        $sm = new ServiceManager([]);

        $sm->myService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockStaticConstructionService::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                13,
                true,
                '%test%',
                '%prefix%-file' // prefix does not exist
            ]
        ];

        $sm->setParameter("test", 188);

        // Suppress warning, to catch it later
        $service = @$sm->get("myService");
        $this->assertEquals([13, true, 188, '%prefix%-file'], $service->arguments);
        $this->assertEquals(512, error_get_last()["type"]);
    }

    public function testGetServiceClass() {
        $sm = new ServiceManager([]);
        $sm->directService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockService::class,
        ];

        $sm->containeredService = new CallbackContainer(function() { return $this; });
        $sm->fileService = [
            AbstractFileConfiguration::SERVICE_FILE => __DIR__ . "/test.mock.php"
        ];

        $sm->containeredService2 = [
            AbstractFileConfiguration::SERVICE_CONTAINER => MockServiceContainer::class,
            AbstractFileConfiguration::CONFIG_SERVICE_TYPE_KEY => MockService::class
        ];

        $sm->loadedService = $this;

        $this->assertFalse($sm->isServiceLoaded("directService"));
        $this->assertEquals(MockService::class, $sm->getServiceClass("directService"));
        $this->assertFalse($sm->isServiceLoaded("directService"));

        $this->assertFalse($sm->isServiceLoaded("containeredService"));
        $this->assertEquals(ServiceManagerTest::class, $sm->getServiceClass("containeredService"));
        $this->assertTrue($sm->isServiceLoaded("containeredService"));

        $this->assertFalse($sm->isServiceLoaded("fileService"));
        $this->assertInstanceOf(MockService::class, $sm->get("fileService"));
        $this->assertTrue($sm->isServiceLoaded("fileService"));

        $this->assertFalse($sm->isServiceLoaded("containeredService2"));
        $this->assertEquals(MockService::class, $sm->getServiceClass("containeredService2"));
        $this->assertFalse($sm->isServiceLoaded("containeredService2"));

        $this->assertTrue($sm->isServiceLoaded("loadedService"));
        $this->assertEquals(ServiceManagerTest::class, $sm->getServiceClass("loadedService"));
        $this->assertTrue($sm->isServiceLoaded("loadedService"));

        $this->assertNull($sm->getServiceClass("ninexistingService"));
    }

    public function testYieldServices() {
        $sm = new ServiceManager([]);
        $sm->directService = [
            AbstractFileConfiguration::SERVICE_CLASS => MockService::class,
        ];

        $sm->containeredService = new CallbackContainer(function() { return $this; });
        $sm->fileService = [
            AbstractFileConfiguration::SERVICE_FILE => __DIR__ . "/test.mock.php"
        ];

        $sm->containeredService2 = [
            AbstractFileConfiguration::SERVICE_CONTAINER => MockServiceContainer::class,
            AbstractFileConfiguration::CONFIG_SERVICE_TYPE_KEY => MockServiceArgumented::class
        ];

        $sm->awareContaineredService = new MockAwareContainer();

        $sm->loadedService = $this;

        $gen = $sm->yieldServices(['fileService'], [MockService::class], false);
        $this->assertInstanceOf(Generator::class, $gen);

        $this->assertFalse($sm->isServiceLoaded("directService"));
        $this->assertFalse($sm->isServiceLoaded("containeredService"));
        $this->assertFalse($sm->isServiceLoaded("fileService"));
        $this->assertFalse($sm->isServiceLoaded("containeredService2"));
        $this->assertTrue($sm->isServiceLoaded("loadedService"));
        $this->assertFalse($sm->isServiceLoaded("awareContaineredService"));

        $this->assertEquals([
            'directService',
            'fileService'
        ], array_keys($sm->getServices(['fileService'], [MockService::class], ServiceManager::OPTION_RETURN_PROMISES | ServiceManager::OPTION_FORCE_CLASS_DETECTION )));

        $this->assertEquals([
            'directService',
        ], array_keys($sm->getServices([], [MockService::class], ServiceManager::OPTION_RETURN_PROMISES | ServiceManager::OPTION_FORCE_CLASS_DETECTION )));

        $this->assertFalse($sm->isServiceLoaded("directService"));
        // Must load containeredService because no type specifier was set
        $this->assertTrue($sm->isServiceLoaded("containeredService"));
        $this->assertTrue($sm->isServiceLoaded("fileService"));
        $this->assertFalse($sm->isServiceLoaded("containeredService2"));
        $this->assertTrue($sm->isServiceLoaded("loadedService"));
        $this->assertFalse($sm->isServiceLoaded("awareContaineredService"));

        $this->assertEquals([
            'directService',
            'fileService',
            'containeredService2'
        ], array_keys($sm->getServices(['fileService'], [MockService::class])));

        $this->assertEquals(['serviceManager' => $sm], $sm->getServices(["serviceManager"], [], ~ServiceManager::OPTION_RETURN_PROMISES));
        $this->assertEquals(['serviceManager' => $sm], $sm->getServices([], [ServiceManager::class], ~ServiceManager::OPTION_RETURN_PROMISES));
    }
}

class MockService {

}

class MockServiceArgumented extends MockService {
    public $arguments;
    public function __construct($data, $test, $service)
    {
        $this->arguments = func_get_args();
    }
}

class MockServiceArgumentedAware extends MockServiceArgumented implements ConstructorAwareServiceInterface {
    public static function getConstructorArguments(): ?array
    {
        return [
            17,
            '$testService',
            'bool' => NULL
        ];
    }
}

class MockStaticConstructionService implements StaticConstructorServiceInterface {
    public $arguments;
    public $sm;

    public function __construct($arguments = NULL, ServiceManager $serviceManager = NULL)
    {
        $this->arguments = $arguments;
        $this->sm = $serviceManager;
    }
}

class MockConfigService {
    public $arguments;
    public $config;

    public function __construct(...$arguments)
    {
        $this->arguments = $arguments;
    }

    public function setConfiguration($cfg) {
        $this->config = $cfg;
    }
}

class MockServiceContainer extends AbstractContainer {
    protected function loadInstance()
    {
        $this->instance = new MockService();
    }
}

class MockAwareContainer extends AbstractContainer implements ServiceAwareContainerInterface {
    protected function loadInstance()
    {
    }

    public function getServiceClass(): string
    {
        return MockConfigService::class;
    }
}


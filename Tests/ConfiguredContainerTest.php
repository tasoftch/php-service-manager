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
 * ConfiguredContainerTest.php
 * service-manager
 *
 * Created on 2019-04-08 17:59 by thomas
 */

use PHPUnit\Framework\TestCase;
use TASoft\Service\Config\AbstractFileConfiguration;
use TASoft\Service\Container\AbstractContainer;
use TASoft\Service\Container\ServiceAwareContainerInterface;
use TASoft\Service\ServiceManager;

class ConfiguredContainerTest extends TestCase
{
    public function testConfiguredByClass() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CLASS => stdClass::class
            ]
        ]);

        $this->assertInstanceOf(stdClass::class, $sm->get("myService"));
    }

    public function testConfiguredByContainer() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CONTAINER => MyContainer::class
            ]
        ]);

        $this->assertInstanceOf(stdClass::class, $sm->get("myService"));
    }

    public function testConfiguredByFile() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_FILE => __DIR__ . "/test.mock.php"
            ]
        ]);

        $this->assertInstanceOf(MockService::class, $sm->get("myService"));
    }

    public function testClassCheck() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CONTAINER => MyContainer::class
            ]
        ]);

        MyContainer::$didLoad = false;

        $this->assertEquals(stdClass::class, $sm->getServiceClass("myService"));

        $this->assertTrue(MyContainer::$didLoad);
    }

    public function testAwareCheck() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CONTAINER => MyAwareContainer::class
            ]
        ]);

        MyAwareContainer::$didLoad = false;
        $this->assertEquals(MyAwareContainer::class, $sm->getServiceClass("myService"));

        $this->assertFalse(MyAwareContainer::$didLoad);
    }

    public function testConfiguredAwareService() {
        $sm = new ServiceManager([
            'myService' => [
                AbstractFileConfiguration::SERVICE_CONTAINER => MyContainer::class,
                AbstractFileConfiguration::CONFIG_SERVICE_TYPE_KEY => stdClass::class
            ]
        ]);

        MyContainer::$didLoad = false;

        $this->assertEquals(stdClass::class, $sm->getServiceClass("myService"));

        $this->assertFalse(MyContainer::$didLoad);
    }
}

class MyContainer extends AbstractContainer {
    public static $didLoad = false;

    protected function loadInstance()
    {
        self::$didLoad = true;
        return new stdClass();
    }
}

class MyAwareContainer extends AbstractContainer implements ServiceAwareContainerInterface {
    public static $didLoad = false;

    protected function loadInstance()
    {
        self::$didLoad = true;
        return $this;
    }

    public function getServiceClass(): string
    {
        return self::class;
    }
}

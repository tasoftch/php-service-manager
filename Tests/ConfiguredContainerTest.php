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
}

class MyContainer extends AbstractContainer {
    protected function loadInstance()
    {
        return new stdClass();
    }
}

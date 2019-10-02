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


use TASoft\Service\Exception\UnknownServiceException;

/**
 * The forwarder trait extends any class to direct property access to services
 * @package TASoft\Service
 */
trait ServiceForwarderTrait
{
    /**
     * Provide a service manager
     * @return null|ServiceManager
     */
    protected function getServiceManager(): ?ServiceManager {
        return ServiceManager::generalServiceManager();
    }

    /**
     * @param $name
     * @return null|object
     */
    public function __get($name)
    {
        $sm = $this->getServiceManager();
        return $sm->get($name);
    }

    /**
     * @param string $serviceName
     * @param object $object
     */
    public function __set($serviceName, $object)
    {
        $sm = $this->getServiceManager();
        if($sm) {
            $sm->set($serviceName, $object);
        }
    }

    /**
     * Returns service if exist
     *
     * @param $serviceName
     * @return object|null
     */
    public function get($serviceName) {
        try {
            $sm = $this->getServiceManager();
            return $sm->get($serviceName);
        } catch (UnknownServiceException $exception) {
            return NULL;
        }
    }
}
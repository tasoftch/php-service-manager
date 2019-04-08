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

use TASoft\Collection\AbstractCollection;
use TASoft\PHP\Exception\FunctionNotFoundException;
use TASoft\PHP\SignatureService;
use TASoft\Service\ConstructorAwareServiceInterface;
use TASoft\Service\DynamicConstructorServiceInterface;
use TASoft\Service\ServiceManager;

/**
 * @inheritdoc
 * @package TASoft\Service
 */
abstract class AbstractContainer implements ContainerInterface {
    /**
     * The singleton instance
     * @var object
     */
    protected $instance;

    /**
     * Implement this method to finally load the service instance from configuration, environment or what else.
     * After this method call, the instance property of the factory object should hold the service instance.
     * @return void|object
     */
    abstract protected function loadInstance();


    /**
     * The default implementation checks if the instance already was loaded, if not, it will load it, store it and return.
     * @return object
     * @see AbstractContainer::loadInstanceIfNeeded()
     * @see AbstractContainer::loadInstance()
     */
    public function getInstance() {
        if(!$this->isInstanceLoaded()) {
            $inst = $this->loadInstance();
            if(!$this->instance)
                $this->instance = $inst;
        }
		return $this->instance;
	}

    /**
     * @inheritDoc
     */
    public function isInstanceLoaded(): bool
    {
        return $this->instance ? true : false;
    }
}
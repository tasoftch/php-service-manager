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

namespace TASoft\Service\Config;

/**
 * The configuration is build like
 *      "serviceName" => [
 *          'container|factory|file' => ... The class name, container class name or a file name to receive the instance.
 *          'arguments' => [...] an index based array for passing arguments to the service/factory constructor.
 *                                  In case of file construction the arguments are passed as $ARGUMENTS
 *          'configuration' => [...] a key value based array containing configurations passed after creation
 *                                      using the setConfiguration() method if available.
 *      ]
 *
 * Note that arguments and configuration may reference other services:
 * [
 *   ...,
 *   'otherService' => '$serviceName' // Note the $ sign!
 * ]
 * or parameters, which can be passed to the service manager and resolved right before instantiation of a service instance:
 * [
 *   ...,
 *   'dnmane' => '%dataBaseName%' // Note the leading and trailing % sign!
 * ]
 *
 *
 * @package TASoft\Service\Config
 */
abstract class AbstractFileConfiguration
{
    /**
     * One of these three keys must be available in a service config set to specify how the instance should be loaded
     */

    /** @var string Load directly by class name */
    const SERVICE_CLASS = 'class';

    /** @var string Load a container and this will provide the service instance */
    const SERVICE_CONTAINER = 'container';

    /** @var string Execute a file and its return value is the instance */
    const SERVICE_FILE = 'file';

    /** @var string Arguments passed to the service instance constructor */
    const SERVICE_INIT_ARGUMENTS = 'arguments';

    /** @var string Configuration set by setConfiguration method of instantiated service if available */
    const SERVICE_INIT_CONFIGURATION = 'configuration';

    /*
     * NOTE! The main difference between arguments and configuration is that
     * arguments are ALWAYS transformed into an array with numeric indexes.
     * The configuration is passed to setConfiguration as is.
     * So you can use iterable objects as configuration and arguments, but
     * only in setConfiguration the original objects are injected.
     * Using StaticConstructorServiceInterface will pass arguments with maintained keys
     * but also transformed to an array.
     */

    /** @var string In case of containers, declare of which class name the service instance will be. */
    const CONFIG_SERVICE_TYPE_KEY = 'type';

    /** @var string If set and TRUE, the service is initialized right after loading the configuration */
    const CONFIG_SERVICE_INIT_ON_LOAD_KEY = 'init';
}
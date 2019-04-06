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
 *          'class|factory|file' => ... The class name, factory class name or a file name to receive the instance.
 *          'arguments' => [...] an index based array for passing arguments to the service/factory constructor.
 *                                  In case of file construction the arguments are passed as $ARGUMENTS
 *          'configuration' => [...] a key value based array containig configurations passed after creation
 *                                      using the setConfiguration() method if available.
 *      ]
 * @package TASoft\Service\Config
 */
abstract class AbstractFileConfiguration
{
    const SERVICE_CLASS = 'class';
    const SERVICE_FACTORY = 'factory';
    const SERVICE_FILE = 'file';

    const SERVICE_INIT_ARGUMENTS = 'arguments';
    const SERVICE_INIT_CONFIGURATION = 'configuration';
}
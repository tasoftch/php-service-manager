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

/**
 * Use this interface for services that know about their construction method signature
 * @package TASoft\Service
 */
interface ConstructorAwareServiceInterface extends ServiceInterface
{
    /**
     * Provide an array with the constructor arguments.
     * The values may contain service references ($serviceName), parameter placeholders (%param%) or be NULL to replace keyed argument with configuration value
     * @example
     * Config: [
     *    'class' => SomeClass::class,
     *    'arguments' => [
     *       'A' => 3,
     *       'C' => 18
     *    ]
     * ]
     * method SomeClass::getConstructorArguments() returns:
     * [
     *   '$logService',
     *   '%fileParameter%',
     *   '%prefix%-file.txt',
     *   'C' => NULL            // This argument will be replaced with the config's C => 18 value.
     * ]
     *
     *
     * Markers like $serviceManager are allowed.
     * @return array|null
     */
    public static function getConstructorArguments(): ?array ;
}
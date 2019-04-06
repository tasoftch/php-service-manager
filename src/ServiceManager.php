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

use TASoft\Service\Exception\ServiceException;

/**
 * The service manager reads from a configuration the registered services and instantiate them on demand.
 *
 * The specifier following the $ sign represents the requested service name.
 *
 * @package TASoft\Service
 */
class ServiceManager
{
    private static $serviceManager;
    private static $globalVariableName = "SERVICES";

    private $serviceData = [];
    private $selfReferenceNames = [
        'serviceManager',
        "SERVICES"
    ];

    /**
     * Returns the service manager. The first call of this method should pass a service config info.
     * @param iterable|NULL $serviceConfig
     * @return ServiceManager
     */
    public static function generalServiceManager(Iterable $serviceConfig = NULL, $selfRefNames= []) {
        if(!static::$serviceManager) {
            if(is_null($serviceConfig))
                throw new ServiceException(sprintf("First call of %s must pass a service configuration", __METHOD__), 13);

            /** @var ServiceManager $man */
            $man = static::$serviceManager = new static($serviceConfig);
            $man->selfReferenceNames = array_merge($man->selfReferenceNames, $selfRefNames);

            if($n = self::getGlobalVariableName())
                $GLOBALS[$n] = $man;
        }
        return static::$serviceManager;
    }

    public static function rejectGeneralServiceManager() {
        static::$serviceManager = NULL;
        if($n = self::getGlobalVariableName())
            unset($GLOBALS[$n]);
    }

    public function __construct(Iterable $config) {
        foreach($config as $serviceName => $serviceConfig) {
            $container = new ServiceContainer($serviceName, $serviceConfig, $this);
            $this->set($serviceName, $container);
        }
    }

    /**
     * @return string
     */
    public static function getGlobalVariableName(): string
    {
        return self::$globalVariableName;
    }

    /**
     * @param string $globalVariableName
     */
    public static function setGlobalVariableName(string $globalVariableName): void
    {
        self::$globalVariableName = $globalVariableName;
    }
}
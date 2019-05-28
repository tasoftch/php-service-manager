<?php

namespace TASoft\Service\Exception;


use ArrayAccess;

class BadConfigurationException extends InvalidServiceException
{
    /** @var array|iterable|ArrayAccess */
    private $configuration;

    /**
     * @return array|ArrayAccess|iterable
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array|ArrayAccess|iterable $configuration
     */
    public function setConfiguration($configuration): void
    {
        $this->configuration = $configuration;
    }
}
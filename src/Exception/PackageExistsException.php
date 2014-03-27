<?php

namespace Spiffy\Package\Exception;

class PackageExistsException extends \InvalidArgumentException
{
    /**
     * @param string $packageName
     */
    public function __construct($packageName)
    {
        return parent::__construct(sprintf(
            'Package with name "%s" already exists',
            $packageName
        ));
    }
}

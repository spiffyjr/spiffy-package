<?php

namespace Spiffy\Package\Exception;

class PackageDoesNotExistException extends \InvalidArgumentException
{
    /**
     * @param string $packageName
     */
    public function __construct($packageName)
    {
        return parent::__construct(sprintf(
            'Package with name "%s" does not exist',
            $packageName
        ));
    }
}

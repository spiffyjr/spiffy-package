<?php

namespace Spiffy\Package\Exception;

class PackageLoadFailedException extends \RuntimeException
{
    /**
     * @param string $packageName
     */
    public function __construct($packageName)
    {
        return parent::__construct(sprintf(
            'Package "%s" failed to load: check your package name and composer autoloading',
            $packageName
        ));
    }
}

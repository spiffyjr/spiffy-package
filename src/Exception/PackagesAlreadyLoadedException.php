<?php

namespace Spiffy\Package\Exception;

class PackagesAlreadyLoadedException extends \RuntimeException
{
    public function __construct()
    {
        return parent::__construct('Packages can not be added after loading is complete');
    }
}

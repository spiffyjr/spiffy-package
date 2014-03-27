<?php

namespace Spiffy\Package\Feature\Exception;

class MissingOptionException extends \InvalidArgumentException
{
    /**
     * @param string $packageName
     */
    public function __construct($packageName)
    {
        return parent::__construct(sprintf(
            'Option with key "%s" does not exist',
            $packageName
        ));
    }
}

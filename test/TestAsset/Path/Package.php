<?php

namespace Spiffy\Package\TestAsset\Path;

use Spiffy\Package\Feature\PathProviderInterface;

class Package implements PathProviderInterface
{
    /**
     * @return string
     */
    public function getPath()
    {
        return __DIR__;
    }
}

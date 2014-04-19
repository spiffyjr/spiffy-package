<?php

namespace Spiffy\Package\TestAsset\Path;

use Spiffy\Package\Feature\OptionsProviderTrait;
use Spiffy\Package\Feature\PathProvider;

class Package implements PathProvider
{
    /**
     * @return string
     */
    public function getPath()
    {
        return __DIR__;
    }
}

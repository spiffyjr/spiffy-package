<?php

namespace Spiffy\Package\TestAsset\Application;

use Spiffy\Package\Feature\ConfigProvider;

class Package implements ConfigProvider
{
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return ['foo' => 'bar'];
    }
}

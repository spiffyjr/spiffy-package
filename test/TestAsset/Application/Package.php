<?php

namespace Spiffy\Package\TestAsset\Application;

use Spiffy\Package\Feature\ConfigProviderInterface;

class Package implements ConfigProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return ['foo' => 'bar'];
    }
}

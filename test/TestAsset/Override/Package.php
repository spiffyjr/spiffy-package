<?php

namespace Spiffy\Package\TestAsset\Override;

use Spiffy\Package\Feature\ConfigProviderInterface;

class Package implements ConfigProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return ['bar' => 'foo', 'foo' => 'foobar', 'baz' => 'baz'];
    }
}

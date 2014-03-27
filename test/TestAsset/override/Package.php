<?php

namespace Spiffy\Package\TestAsset\Override;

use Spiffy\Package\Feature\ConfigProvider;

class Package implements ConfigProvider
{
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return ['bar' => 'foo', 'foo' => 'foobar', 'baz' => 'baz'];
    }
}

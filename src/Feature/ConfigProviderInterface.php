<?php

namespace Spiffy\Package\Feature;

interface ConfigProviderInterface
{
    /**
     * @return array
     */
    public function getConfig();
}

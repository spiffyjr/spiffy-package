<?php

namespace Spiffy\Package\Feature;

interface OptionsProvider
{
    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param string $key
     * @return mixed
     */
    public function getOption($key);

    /**
     * @param array $options
     * @return void
     */
    public function setOptions(array $options);
}

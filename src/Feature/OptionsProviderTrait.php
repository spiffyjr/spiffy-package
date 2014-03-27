<?php

namespace Spiffy\Package\Feature;

use Spiffy\Package\Feature\Exception;

trait OptionsProviderTrait
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param mixed $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $key
     * @throws Exception\MissingOptionException
     * @return mixed
     */
    public function getOption($key)
    {
        if (!array_key_exists($key, $this->options)) {
            throw new Exception\MissingOptionException($key);
        }
        return $this->options[$key];
    }
}

<?php

namespace Spiffy\Package\Plugin;

use Spiffy\Event\Event;
use Spiffy\Event\Plugin;
use Spiffy\Event\Manager;
use Spiffy\Package\Exception;
use Spiffy\Package\Feature\ConfigProvider;
use Spiffy\Package\PackageManager;

class ConfigMergePlugin implements Plugin
{
    /**
     * @var string
     */
    protected $overridePattern;

    /**
     * @var int
     */
    protected $overrideFlags;

    /**
     * @param string $pattern
     * @param int $flags
     */
    public function __construct($pattern = null, $flags = 0)
    {
        $this->overridePattern = $pattern;
        $this->overrideFlags = $flags;
    }

    /**
     * {@inheritDoc}
     */
    public function plug(Manager $events)
    {
        $events->on(PackageManager::EVENT_MERGE_CONFIG, [$this, 'onMergeConfig']);
    }

    /**
     * @param Event $e
     * @return array
     */
    public function onMergeConfig(Event $e)
    {
        /** @var \Spiffy\Package\PackageManager $packageManager */
        $packageManager = $e->getTarget();
        $config = [];

        foreach ($packageManager->getPackages() as $package) {
            if ($package instanceof ConfigProvider) {
                $config = $packageManager->merge($config, $package->getConfig());
            }
        }

        if (null === $this->overridePattern) {
            return $config;
        }

        $overrideFiles = glob($this->overridePattern, $this->overrideFlags);
        foreach ($overrideFiles as $file) {
            // @codeCoverageIgnoreStart
            // This should never be reached because glob() should only return valid files
            // but there were instances that HHVM was getting here so this allowed the code to execute
            // and not produce failures.
            //if (empty($file) || !file_exists($file)) {
                //continue;
            //}
            // @codeCoverageIgnoreEnd
            $config = $packageManager->merge($config, include $file);
        }

        return $config;
    }
}

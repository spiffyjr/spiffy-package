<?php

namespace Spiffy\Package\Feature;

use Spiffy\Event\Event;
use Spiffy\Event\Listener;
use Spiffy\Event\Manager;
use Spiffy\Package\PackageManager;

class OptionsProviderFeature implements Listener
{
    /**
     * {@inheritDoc}
     */
    public function attach(Manager $events)
    {
        $events->on(PackageManager::EVENT_LOAD_POST, [$this, 'onLoadPost'], -1000);
    }

    /**
     * @param Event $e
     */
    public function onLoadPost(Event $e)
    {
        /** @var \Spiffy\Package\PackageManager $manager */
        $manager = $e->getTarget();
        $config = $manager->getMergedConfig();

        foreach ($manager->getPackages() as $packageName => $package) {
            if ($package instanceof OptionsProvider) {
                if (isset($config[$packageName])) {
                    $package->setOptions($config[$packageName]);
                }
            }
        }
    }
}

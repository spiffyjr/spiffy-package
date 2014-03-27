<?php

namespace Spiffy\Package\Listener;

use Spiffy\Event\Event;
use Spiffy\Event\Listener;
use Spiffy\Event\Manager;
use Spiffy\Package\Exception;
use Spiffy\Package\PackageManager;

class LoadModulesListener implements Listener
{
    /**
     * {@inheritDoc}
     */
    public function attach(Manager $events)
    {
        $events->on(PackageManager::EVENT_LOAD, [$this, 'onLoad'], 1000);
    }

    /**
     *
     * @throws Exception\PackageLoadFailedException
     */
    public function onLoad(Event $e)
    {
        /** @var \Spiffy\Package\PackageManager $manager */
        $manager = $e->getTarget();
        $packages = $manager->getPackages();

        foreach ($packages as $packageName => $package) {
            $event = new Event(PackageManager::EVENT_RESOLVE, $packageName);
            $result = $manager->events()->fire($event);
            $package = $result->isEmpty() ? null : $result->top();

            if (null === $package) {
                throw new Exception\PackageLoadFailedException($packageName);
            }

            $packages[$packageName] = $package;
        }
    }
}

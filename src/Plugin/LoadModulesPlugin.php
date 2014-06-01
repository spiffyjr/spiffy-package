<?php

namespace Spiffy\Package\Plugin;

use Spiffy\Event\Event;
use Spiffy\Event\Plugin;
use Spiffy\Event\Manager;
use Spiffy\Package\Exception;
use Spiffy\Package\PackageManager;

class LoadModulesPlugin implements Plugin
{
    /**
     * {@inheritDoc}
     */
    public function plug(Manager $events)
    {
        $events->on(PackageManager::EVENT_LOAD, [$this, 'onLoad'], 1000);
        $events->plug(new ResolvePackagePlugin());
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

        $event = new Event(PackageManager::EVENT_RESOLVE);
        foreach ($packages as $packageName => $package) {
            $event->setTarget($packageName);
            $event->set('fqcn', is_string($package) ? $package : null);

            $result = $manager->events()->fire($event);
            $package = $result->isEmpty() ? null : $result->top();

            if (null === $package) {
                throw new Exception\PackageLoadFailedException($packageName);
            }

            $packages[$packageName] = $package;
        }
    }
}

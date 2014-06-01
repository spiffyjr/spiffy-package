<?php

namespace Spiffy\Package\Plugin;

use Spiffy\Event\Event;
use Spiffy\Event\Plugin;
use Spiffy\Event\Manager;
use Spiffy\Package\PackageManager;

class ResolvePackagePlugin implements Plugin
{
    /**
     * {@inheritDoc}
     */
    public function plug(Manager $events)
    {
        $events->on(PackageManager::EVENT_RESOLVE, [$this, 'onResolve']);
    }

    /**
     * @param Event $e
     * @return object|null
     */
    public function onResolve(Event $e)
    {
        $name = $e->get('fqcn');

        if (empty($name)) {
            $name = $e->getTarget() . '\\Package';
        }

        return class_exists($name) ? new $name() : null;
    }
}

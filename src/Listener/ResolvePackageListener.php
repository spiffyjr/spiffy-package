<?php

namespace Spiffy\Package\Listener;

use Spiffy\Event\Event;
use Spiffy\Event\Listener;
use Spiffy\Event\Manager;
use Spiffy\Package\PackageManager;

class ResolvePackageListener implements Listener
{
    /**
     * {@inheritDoc}
     */
    public function attach(Manager $events)
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
            $name = ucfirst(strtolower($e->getTarget()));

            $name = preg_replace_callback('@-([a-z])@', function ($match) {
                return ucfirst($match[1]);
            }, $name);

            $name = preg_replace_callback('@\.([a-z])@', function ($match) {
                return '\\' . ucfirst($match[1]);
            }, $name);

            $name = $name . '\\Package';
        }

        return class_exists($name) ? new $name() : null;
    }
}

<?php

namespace Spiffy\Package\Listener;

use Spiffy\Event\Event;
use Spiffy\Event\EventManager;
use Spiffy\Package\PackageManager;

/**
 * @coversDefaultClass \Spiffy\Package\Listener\LoadModulesListener
 */
class LoadModulesListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::attach
     */
    public function testAttach()
    {
        $events = new EventManager();
        $feature = new LoadModulesListener();
        $feature->attach($events);

        $this->assertCount(1, $events->getEvents(PackageManager::EVENT_LOAD));
    }

    /**
     * @covers ::onLoad, \Spiffy\Package\Exception\PackageLoadFailedException::__construct
     * @expectedException \Spiffy\Package\Exception\PackageLoadFailedException
     * @expectedExceptionMessage Package "foo" failed to load: check your package name and composer autoloading
     */
    public function testOnLoadThrowsExceptionIfModuleCanNotBeResolved()
    {
        $manager = new PackageManager();
        $manager->add('foo');

        $event = new Event(PackageManager::EVENT_LOAD, $manager);

        $feature = new LoadModulesListener();
        $feature->onLoad($event);
    }

    /**
     * @covers ::onLoad
     */
    public function testOnLoad()
    {
        $manager = new PackageManager();
        $manager->add('spiffy.package.test-asset.options');

        $event = new Event(PackageManager::EVENT_LOAD, $manager);

        $feature = new LoadModulesListener();
        $feature->onLoad($event);

        $packages = $manager->getPackages();
        $this->assertInstanceOf(
            'Spiffy\Package\TestAsset\Options\Package',
            $packages['spiffy.package.test-asset.options']
        );
    }
}

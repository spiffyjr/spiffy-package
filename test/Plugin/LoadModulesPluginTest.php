<?php

namespace Spiffy\Package\Plugin;

use Spiffy\Event\Event;
use Spiffy\Event\EventManager;
use Spiffy\Package\PackageManager;

/**
 * @coversDefaultClass \Spiffy\Package\Plugin\LoadModulesPlugin
 */
class LoadModulesPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::plug
     */
    public function testPlug()
    {
        $events = new EventManager();
        $p = new LoadModulesPlugin();
        $p->plug($events);

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

        $p = new LoadModulesPlugin();
        $p->onLoad($event);
    }

    /**
     * @covers ::onLoad
     */
    public function testOnLoad()
    {
        $manager = new PackageManager();
        $manager->add('Spiffy\Package\TestAsset\Application');

        $event = new Event(PackageManager::EVENT_LOAD, $manager);

        $p = new LoadModulesPlugin();
        $p->onLoad($event);

        $packages = $manager->getPackages();
        $this->assertInstanceOf(
            'Spiffy\Package\TestAsset\EsoLfg\Package',
            $packages['Spiffy\Package\TestAsset\Application']
        );
    }
}

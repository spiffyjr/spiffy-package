<?php

namespace Spiffy\Package\Plugin;
use Spiffy\Event\Event;
use Spiffy\Event\EventManager;
use Spiffy\Package\PackageManager;

/**
 * @coversDefaultClass \Spiffy\Package\Plugin\ConfigMergePlugin
 */
class ConfigMergePluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::plug, ::__construct
     */
    public function testPlug()
    {
        $events = new EventManager();
        $p = new ConfigMergePlugin();
        $p->plug($events);

        $this->assertCount(1, $events->getEvents(PackageManager::EVENT_MERGE_CONFIG));
    }

    /**
     * @covers ::onMergeConfig
     */
    public function testOnMergeConfig()
    {
        $e = new Event();
        $e->setTarget(new PackageManager());

        $f = new ConfigMergePlugin();
        $this->assertEmpty($f->onMergeConfig($e));
    }

    /**
     * @covers ::onMergeConfig
     */
    public function testOnMergeConfigWithPackages()
    {
        $pm = new PackageManager();
        $pm->add('Spiffy\Package\TestAsset\Application');
        $pm->load();

        $e = new Event();
        $e->setTarget($pm);

        $f = new ConfigMergePlugin();
        $this->assertSame(['foo' => 'bar'], $f->onMergeConfig($e));
    }

    /**
     * @covers ::onMergeConfig
     */
    public function testOnMergeConfigWithOverride()
    {
        $pm = new PackageManager();
        $pm->add('Spiffy\Package\TestAsset\Application');
        $pm->load();

        $e = new Event();
        $e->setTarget($pm);

        $f = new ConfigMergePlugin(__DIR__ . '/../config/config.php');
        $this->assertSame(['foo' => 'baz'], $f->onMergeConfig($e));
    }
}

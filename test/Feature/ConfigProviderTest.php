<?php

namespace Spiffy\Package\Feature;

use Spiffy\Event\Event;
use Spiffy\Event\EventManager;
use Spiffy\Package\PackageManager;

/**
 * @coversDefaultClass \Spiffy\Package\Feature\OptionsProviderFeature
 */
class OptionsProviderFeatureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::attach
     */
    public function testAttach()
    {
        $events = new EventManager();
        $feature = new OptionsProviderFeature();
        $feature->attach($events);

        $this->assertCount(1, $events->getEvents(PackageManager::EVENT_LOAD_POST));
    }

    /**
     * @covers ::onLoadPost
     */
    public function testOnLoadPostSetsOptions()
    {
        $manager = new PackageManager();
        $manager->add('spiffy.package.test_asset.options');
        $manager->load();

        $refl = new \ReflectionClass($manager);
        $config = $refl->getProperty('mergedConfig');
        $config->setAccessible(true);
        $config->setValue($manager, [
            'spiffy.package.test_asset.options' => [
                'foo' => 'bar'
            ]
        ]);

        $event = new Event('foo', $manager);

        $feature = new OptionsProviderFeature();
        $feature->onLoadPost($event);

        /** @var \Spiffy\Package\TestAsset\Options\Package $package */
        $package = $manager->getPackage('spiffy.package.test_asset.options');
        $this->assertSame('bar', $package->getOption('foo'));
    }
}

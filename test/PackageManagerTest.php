<?php

namespace Spiffy\Package;

/**
 * @coversDefaultClass \Spiffy\Package\PackageManager
 */
class PackageManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PackageManager
     */
    protected $pm;

    /**
     * @covers ::__construct
     */
    public function testPackagesIsInitialized()
    {
        $pm = new PackageManager();
        $this->assertInstanceOf('ArrayObject', $pm->getPackages());
    }

    /**
     * @covers ::getMergedConfig
     */
    public function testMergedConfigIsInitialized()
    {
        $pm = new PackageManager();
        $this->assertInternalType('array', $pm->getMergedConfig());
    }

    /**
     * @covers ::getPackages
     */
    public function testGetPackages()
    {
        $pm = $this->pm;
        $this->assertCount(2, $pm->getPackages());
    }

    /**
     * @covers ::getPackage, \Spiffy\Package\Exception\PackageDoesNotExistException::__construct
     * @expectedException \Spiffy\Package\Exception\PackageDoesNotExistException
     * @expectedExceptionMessage Package with name "foo" does not exist
     */
    public function testGetPackageThrowsExceptionForMissingPackage()
    {
        $pm = $this->pm;
        $pm->getPackage('foo');
    }

    /**
     * @covers ::getPackage, \Spiffy\Package\Exception\PackagesNotLoadedException::__construct
     * @expectedException \Spiffy\Package\Exception\PackagesNotLoadedException
     * @expectedExceptionMessage Packages have not been loaded
     */
    public function testGetPackageThrowsExceptionIfPackagesNotLoaded()
    {
        $pm = $this->pm;
        $pm->getPackage('spiffy.package.test_asset.application');
    }

    /**
     * @covers ::getPackage
     */
    public function testPackage()
    {
        $pm = $this->pm;
        $pm->load();

        $package = $pm->getPackage('spiffy.package.test_asset.application');
        $this->assertInstanceOf('Spiffy\Package\TestAsset\Application\Package', $package);
    }

    /**
     * @covers ::add, \Spiffy\Package\Exception\PackagesAlreadyLoadedException::__construct
     * @expectedException \Spiffy\Package\Exception\PackagesAlreadyLoadedException
     * @expectedExceptionMessage Packages can not be added after loading is complete
     */
    public function testAddThrowsExceptionWhenAlreadyLoaded()
    {
        $pm = $this->pm;
        $pm->load();
        $pm->add('foo');
    }

    /**
     * @covers ::add, \Spiffy\Package\Exception\PackageExistsException::__construct
     * @expectedException \Spiffy\Package\Exception\PackageExistsException
     * @expectedExceptionMessage Package with name "spiffy.package.test_asset.application" already exists
     */
    public function testAddThrowsExceptionWhenPackageExists()
    {
        $pm = $this->pm;
        $pm->add('spiffy.package.test_asset.application');
    }

    /**
     * @covers ::load, ::generateConfig
     */
    public function testLoadFiresEventsAndGeneratesConfig()
    {
        $load = false;
        $loadPost = false;
        $pm = $this->pm;

        $this->assertSame([], $pm->getMergedConfig());

        $pm->events()->on(PackageManager::EVENT_LOAD, function() use (&$load) {
            $load = true;
        });
        $pm->events()->on(PackageManager::EVENT_LOAD_POST, function() use (&$loadPost) {
            $loadPost = true;
        });
        $pm->load();

        $this->assertTrue($load);
        $this->assertTrue($loadPost);
        $this->assertEquals(['foo' => 'foobar', 'bar' => 'foo', 'baz' => 'baz'], $pm->getMergedConfig());
    }

    /**
     * @covers ::load
     */
    public function testLoadReturnsEarlyIfLoadedAlready()
    {
        $pm = $this->pm;

        $refl = new \ReflectionClass($pm);
        $loaded = $refl->getProperty('loaded');
        $loaded->setAccessible(true);

        $this->assertFalse($loaded->getValue($pm));
        $pm->load();
        $this->assertTrue($loaded->getValue($pm));
        $this->assertNull($pm->load());
    }

    protected function setUp()
    {
        $pm = $this->pm = new PackageManager();
        $pm->add('spiffy.package.test_asset.application');
        $pm->add('spiffy.package.test_asset.override');
    }
}

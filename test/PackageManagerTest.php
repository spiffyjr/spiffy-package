<?php

namespace Spiffy\Package;

use Mockery as m;

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
     * @covers ::getPackagePath
     */
    public function testGetPackagePath()
    {
        $pm = $this->pm;
        $pm->add('spiffy.package.test-asset.path');
        $pm->load();

        // Default path is up one dir from location
        $this->assertSame(
            realpath(__DIR__ . '/TestAsset'),
            $pm->getPackagePath('spiffy.package.test-asset.application')
        );

        // Implemeting PathProvider
        $this->assertSame(
            realpath(__DIR__ . '/TestAsset/Path'),
            $pm->getPackagePath('spiffy.package.test-asset.path')
        );
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
     * @covers ::add, ::load
     */
    public function testAddingPackageUsingFqcn()
    {
        $pm = $this->pm;
        $pm->add('spiffy.package.test-asset.fqcn', 'Spiffy\\Package\\TestAsset\\FQCN\\Module');
        $pm->load();

        $package = $pm->getPackage('spiffy.package.test-asset.fqcn');
        $this->assertInstanceOf('Spiffy\\Package\\TestAsset\\FQCN\\Module', $package);
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
        $pm->getPackage('spiffy.package.test-asset.application');
    }

    /**
     * @covers ::getPackage
     */
    public function testPackage()
    {
        $pm = $this->pm;
        $pm->load();

        $package = $pm->getPackage('spiffy.package.test-asset.application');
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
     * @expectedExceptionMessage Package with name "spiffy.package.test-asset.application" already exists
     */
    public function testAddThrowsExceptionWhenPackageExists()
    {
        $pm = $this->pm;
        $pm->add('spiffy.package.test-asset.application');
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
     * @covers ::setOverrideFlags, ::getOverrideFlags
     */
    public function testSetGetOverrideFlags()
    {
        $flags = GLOB_BRACE;

        $pm = $this->pm;
        $pm->setOverrideFlags($flags);

        $this->assertSame($flags, $pm->getOverrideFlags());
    }

    /**
     * @covers ::setOverridePattern, ::getOverridePattern
     */
    public function testSetGetOverridePattern()
    {
        $pattern = '*.php';

        $pm = $this->pm;
        $pm->setOverridePattern($pattern);
        $this->assertSame($pattern, $pm->getOverridePattern());
    }

    /**
     * @covers ::generateConfig, ::getOverrideFiles
     */
    public function testOverrideConfig()
    {
        $expected = [
            'foo' => 'bar',
            'bar' => 'foo',
            'baz' => 'booze',
        ];

        $pm = $this->pm;
        $pm->setOverrideFlags(GLOB_BRACE);
        $pm->setOverridePattern(__DIR__ . '/TestAsset/Application/config/{1,2}*.php');
        $pm->load();

        $this->assertSame($expected, $pm->getMergedConfig());
    }

    /**
     * @covers ::generateConfig
     */
    public function testOverrideConfigSkipsEmptyOrMissingFiles()
    {
        $pm = m::mock('Spiffy\Package\PackageManager[getOverrideFiles]');
        $pm->shouldAllowMockingProtectedMethods(true);
        $pm
            ->shouldReceive('getOverrideFiles')
            ->once()
            ->andReturn(['file' => '']);

        $pm->load();

        $this->assertSame([], $pm->getMergedConfig());
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

    /**
     * @covers ::merge
     */
    public function testMerge()
    {
        $pm = $this->pm;
        $a = ['one' => ['foo', 'bar'], 'two'];
        $b = ['one' => ['baz' => 'booze'], 'three'];

        $refl = new \ReflectionClass($pm);
        $method = $refl->getMethod('merge');
        $method->setAccessible(true);
        $result = $method->invokeArgs($pm, [$a, $b]);

        $this->assertSame(['one' => ['foo', 'bar', 'baz' => 'booze'], 'two', 'three'], $result);
    }

    protected function setUp()
    {
        $pm = $this->pm = new PackageManager();
        $pm->add('spiffy.package.test-asset.application');
        $pm->add('spiffy.package.test-asset.override');
    }
}

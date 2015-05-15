<?php

namespace Spiffy\Package;

use Mockery as m;
use Spiffy\Package\TestAsset\TestHook;

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
     * @covers ::getPath
     */
    public function testGetPath()
    {
        $pm = $this->pm;
        $pm->add('Spiffy\Package\TestAsset\Path');
        $pm->load();

        // Default path is up one dir from location
        $this->assertSame(
            realpath(__DIR__ . '/TestAsset'),
            $pm->getPath('Spiffy\Package\TestAsset\Application')
        );

        // Implemeting PathProvider
        $this->assertSame(
            realpath(__DIR__ . '/TestAsset/Path'),
            $pm->getPath('Spiffy\Package\TestAsset\Path')
        );

        // Reuses cache
        $this->assertSame(
            realpath(__DIR__ . '/TestAsset/Path'),
            $pm->getPath('Spiffy\Package\TestAsset\Path')
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
     * @covers ::add
     * @covers ::load
     */
    public function testAddingPackageUsingFqcn()
    {
        $pm = $this->pm;
        $pm->add('fcqn', 'Spiffy\\Package\\TestAsset\\FQCN\\Module');
        $pm->load();

        $package = $pm->getPackage('fcqn');
        $this->assertInstanceOf('Spiffy\\Package\\TestAsset\\FQCN\\Module', $package);
    }

    /**
     * @covers ::getPackage
     * @covers \Spiffy\Package\Exception\PackageDoesNotExistException::__construct
     * @expectedException \Spiffy\Package\Exception\PackageDoesNotExistException
     * @expectedExceptionMessage Package with name "foo" does not exist
     */
    public function testGetPackageThrowsExceptionForMissingPackage()
    {
        $pm = $this->pm;
        $pm->getPackage('foo');
    }

    /**
     * @covers ::getPackage
     * @covers \Spiffy\Package\Exception\PackagesNotLoadedException::__construct
     * @expectedException \Spiffy\Package\Exception\PackagesNotLoadedException
     * @expectedExceptionMessage Packages have not been loaded
     */
    public function testGetPackageThrowsExceptionIfPackagesNotLoaded()
    {
        $pm = $this->pm;
        $pm->getPackage('Spiffy\Package\TestAsset\Application');
    }

    /**
     * @covers ::getPackage
     */
    public function testPackage()
    {
        $pm = $this->pm;
        $pm->load();

        $package = $pm->getPackage('Spiffy\Package\TestAsset\Application');
        $this->assertInstanceOf('Spiffy\Package\TestAsset\Application\Package', $package);
    }

    /**
     * @covers ::add
     * @covers \Spiffy\Package\Exception\PackagesAlreadyLoadedException::__construct
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
     * @covers ::add
     * @covers \Spiffy\Package\Exception\PackageExistsException::__construct
     * @expectedException \Spiffy\Package\Exception\PackageExistsException
     * @expectedExceptionMessage Package with name "Spiffy\Package\TestAsset\Application" already exists
     */
    public function testAddThrowsExceptionWhenPackageExists()
    {
        $pm = $this->pm;
        $pm->add('Spiffy\Package\TestAsset\Application');
    }

    /**
     * @covers ::load
     * @covers ::writeCache
     * @covers ::getCacheFile
     */
    public function testLoadCachedFile()
    {
        $pm = new PackageManager();
        $pm->load();
        
        $this->assertFileNotExists(sys_get_temp_dir() . '/package.merged.config.php');
        
        $tmp = sys_get_temp_dir();

        $pm = new PackageManager(['cache_dir' => $tmp]);
        $pm->load();

        $file = sys_get_temp_dir() . '/package.merged.config.php';
        $this->assertFileExists($file);

        $result = include $file;

        $this->assertSame($result, $pm->getMergedConfig());
        
        $pm = new PackageManager(['cache_dir' => $tmp]);
        $pm->load();

        $this->assertSame($result, $pm->getMergedConfig());
    }

    /**
     * @covers ::load
     * @covers ::getMergedConfig
     */
    public function testLoadFiresEventsAndGeneratesConfig()
    {
        $pm = new PackageManager();
        $hook = new TestHook();
        $pm->hooks()->add($hook);

        $this->assertSame([], $pm->getMergedConfig());

        $pm->load();

        $this->assertTrue($hook->onLoad);
        $this->assertSame($pm->getMergedConfig(), $hook->mergedConfig);
        $this->assertEquals(['foo' => 'bar'], $pm->getMergedConfig());
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
        $a = ['one' => ['foo', 'bar'], 'stringkey' => 'string', 'two'];
        $b = ['one' => ['baz' => 'booze'], 'stringkey' => 'override', 'three'];

        $refl = new \ReflectionClass($pm);
        $method = $refl->getMethod('merge');
        $method->setAccessible(true);
        $result = $method->invokeArgs($pm, [$a, $b]);

        $this->assertSame(['one' => ['foo', 'bar', 'baz' => 'booze'], 'stringkey' => 'override', 'two', 'three'], $result);
    }

    protected function setUp()
    {
        $pm = $this->pm = new PackageManager();
        $pm->add('Spiffy\Package\TestAsset\Application');
        $pm->add('Spiffy\Package\TestAsset\Override');
    }
    
    protected function tearDown()
    {
        if (file_exists(sys_get_temp_dir() . '/package.merged.config.php')) {
            unlink(sys_get_temp_dir() . '/package.merged.config.php');
        }
    }
}

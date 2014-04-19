<?php

namespace Spiffy\Package;

use Spiffy\Event\EventsAwareTrait;
use Spiffy\Event\Manager as EventManager;
use Spiffy\Package\Feature;
use Spiffy\Package\Feature\PathProvider;
use Spiffy\Package\Listener\LoadModulesListener;

class PackageManager implements Manager
{
    use EventsAwareTrait;

    const EVENT_LOAD = 'load';
    const EVENT_LOAD_POST = 'load.post';
    const EVENT_LOAD_PACKAGE = 'load.package';
    const EVENT_RESOLVE = 'resolve';

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var array
     */
    protected $pathCache = [];

    /**
     * @var array
     */
    protected $mergedConfig = [];

    /**
     * @var string
     */
    protected $overridePattern;

    /**
     * @var integer
     */
    protected $overrideFlags = 0;

    /**
     * @var \ArrayObject
     */
    protected $packages;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packages = new \ArrayObject();
    }

    /**
     * @param int $overrideFlags
     */
    public function setOverrideFlags($overrideFlags)
    {
        $this->overrideFlags = $overrideFlags;
    }

    /**
     * @return int
     */
    public function getOverrideFlags()
    {
        return $this->overrideFlags;
    }

    /**
     * @param string $overridePattern
     */
    public function setOverridePattern($overridePattern)
    {
        $this->overridePattern = $overridePattern;
    }

    /**
     * @return string
     */
    public function getOverridePattern()
    {
        return $this->overridePattern;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception\PackageDoesNotExistException
     * @throws Exception\PackagesNotLoadedException
     */
    public function getPackage($name)
    {
        if (!$this->packages->offsetExists($name)) {
            throw new Exception\PackageDoesNotExistException($name);
        }

        if (null === $this->packages[$name]) {
            throw new Exception\PackagesNotLoadedException();
        }

        return $this->packages[$name];
    }

    /**
     * @return \ArrayObject
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getPackagePath($name)
    {
        $package = $this->getPackage($name);

        if ($package instanceof PathProvider) {
            return $package->getPath();
        }

        if (!isset($this->pathCache[$name])) {
            $refl = new \ReflectionClass($package);
            $this->pathCache[$name] = realpath(dirname($refl->getFileName()) . '/..');
        }
        return $this->pathCache[$name];
    }

    /**
     * @param string $name
     * @param null|string $fqcn
     * @throws Exception\PackageExistsException
     * @throws Exception\PackagesAlreadyLoadedException
     */
    public function add($name, $fqcn = null)
    {
        if ($this->loaded) {
            throw new Exception\PackagesAlreadyLoadedException();
        }

        if ($this->packages->offsetExists($name)) {
            throw new Exception\PackageExistsException($name);
        }

        $this->packages[$name] = $fqcn;
    }

    /**
     * Performs the loading of modules by firing the load event, merging the configurations,
     * and firing the load post event.
     */
    public function load()
    {
        if ($this->loaded) {
            return;
        }

        $this->events()->fire(static::EVENT_LOAD, $this);
        $this->generateConfig();
        $this->events()->fire(static::EVENT_LOAD_POST, $this);

        $this->loaded = true;
    }

    /**
     * @return array
     */
    public function getMergedConfig()
    {
        return $this->mergedConfig;
    }

    /**
     * @return void
     */
    protected function generateConfig()
    {
        foreach ($this->packages as $package) {
            if ($package instanceof Feature\ConfigProvider) {
                $this->mergedConfig = $this->merge($this->mergedConfig, $package->getConfig());
            }
        }

        $override = $this->getOverrideFiles();
        foreach ($override as $file) {
            if (empty($file) || !file_exists($file)) {
                continue;
            }
            $this->mergedConfig = $this->merge($this->mergedConfig, include $file);
        }
    }

    /**
     * @return array
     */
    protected function getOverrideFiles()
    {
        return glob($this->overridePattern, $this->overrideFlags);
    }

    /**
     * Taken from ZF2's ArrayUtils::merge() method.
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function merge(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = $this->merge($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * {@inheritDoc}
     */
    protected function initEvents(EventManager $events)
    {
        $events->attach(new LoadModulesListener());
        $events->attach(new Feature\OptionsProviderFeature());
    }
}

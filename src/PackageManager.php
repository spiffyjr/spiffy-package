<?php

namespace Spiffy\Package;

use Spiffy\Event\EventManager;
use Spiffy\Event\EventsAwareTrait;
use Spiffy\Package\Plugin;

final class PackageManager implements Manager
{
    use EventsAwareTrait;

    const EVENT_LOAD = 'spiffy.package:load';
    const EVENT_LOAD_POST = 'spiffy.package:load.post';
    const EVENT_LOAD_PACKAGE = 'spiffy.package:load.package';
    const EVENT_MERGE_CONFIG = 'spiffy.package:merge.config';
    const EVENT_RESOLVE = 'spiffy.package:resolve';

    /** @var string|null */
    private $cacheDir;
    /** @var null|string */
    protected $overridePattern;
    /** @var int */
    protected $overrideFlags;
    /** @var bool */
    protected $loaded = false;
    /** @var array */
    protected $pathCache = [];
    /** @var array */
    protected $mergedConfig = [];
    /** @var \ArrayObject */
    protected $packages;

    /**
     * @param string $overridePattern
     * @param int $overrideFlags
     * @param string|null $cacheDir
     */
    public function __construct($overridePattern = null, $overrideFlags = 0, $cacheDir = null)
    {
        $this->cacheDir = $cacheDir;
        $this->overridePattern = $overridePattern;
        $this->overrideFlags = $overrideFlags;
        $this->packages = new \ArrayObject();
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
    public function getPath($name)
    {
        if (isset($this->pathCache[$name])) {
            return $this->pathCache[$name];
        }

        $package = $this->getPackage($name);
        if ($package instanceof Feature\PathProvider) {
            $this->pathCache[$name] = $package->getPath();
        } else {
            $refl = new \ReflectionObject($package);
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
        
        $cacheFile = $this->cacheDir ? $this->cacheDir . '/package.merged.config.php' : null;
        
                
        if ($cacheFile && file_exists($cacheFile)) {
            $this->mergedConfig = include $cacheFile;
        } else {
            foreach ($this->events()->fire(static::EVENT_MERGE_CONFIG, $this) as $response) {
                $this->mergedConfig = $this->merge($this->mergedConfig, $response);
            }
            
            if (is_writeable(dirname($cacheFile))) {
                file_put_contents(
                    $cacheFile,
                    sprintf(
                        '<?php return %s;',
                        var_export($this->mergedConfig, true)
                    )
                );
            }
        }

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
     * Taken from ZF2's ArrayUtils::merge() method.
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    public function merge(array $a, array $b)
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
    protected function attachDefaultPlugins(EventManager $events)
    {
        $events->plug(new Plugin\ConfigMergePlugin($this->overridePattern, $this->overrideFlags));
        $events->plug(new Plugin\LoadModulesPlugin());
    }
}

<?php

namespace Spiffy\Package;

use Hookline\Exception\InvalidHookException;
use Hookline\HookInterface;
use Hookline\HooksAwareInterface;
use Hookline\HooksAwareTrait;

final class PackageManager implements HooksAwareInterface, ManagerInterface
{
    use HooksAwareTrait;

    const CACHE_FILE_NAME = 'package.merged.config.php';

    /** @var array */
    private $config = [];
    /** @var array */
    private $configDefaults = [
        'cache_dir' => null,
        'override_pattern' => null,
        'override_flags' => 0,
    ];

    /** @var bool */
    protected $loaded = false;
    /** @var array */
    protected $pathCache = [];
    /** @var array */
    protected $mergedConfig = [];
    /** @var \ArrayObject */
    protected $packages;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->packages = new \ArrayObject();

        $this->setConfigDefaults($config);
        $this->installDefaultHooks();
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
        if ($package instanceof Feature\PathProviderInterface) {
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

        $this->hooks()->run('onLoad', $this);
        $cacheFile = $this->getCacheFile();
        
        if ($cacheFile && file_exists($cacheFile)) {
            $this->mergedConfig = include $cacheFile;
        } else {
            foreach ($this->hooks()->run('onMerge', $this) as $config) {
                if (empty($config)) {
                    continue;
                }
                
                $this->mergedConfig = $this->merge($this->mergedConfig, $config);
            }
            $this->writeCache();
        }

        $this->hooks()->run('afterLoad', $this, $this->mergedConfig);
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
     * Borrowed from ZF2's ArrayUtils::merge() method.
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
     * Writes config to filesystem.
     */
    public function writeCache()
    {
        $cacheFile = $this->getCacheFile();
        if (!$cacheFile) {
            return;
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

    /**
     * @return null|string
     */
    public function getCacheFile()
    {
        return $this->config['cache_dir'] ? $this->config['cache_dir'] . '/' . self::CACHE_FILE_NAME : null;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    private function setConfigDefaults(array $config)
    {
        $this->config = array_merge($this->configDefaults, $config);

        if (!isset($this->config['hooks'])) {
            $this->config['hooks'] = [new DefaultPackageHook()];
        }
    }

    private function installDefaultHooks()
    {
        foreach ($this->config['hooks'] as $hook) {
            if (!$hook instanceof HookInterface) {
                throw new InvalidHookException();
            }
            $this->hooks()->add($hook);
        }
    }
}

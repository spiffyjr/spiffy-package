<?php

namespace Spiffy\Package;

use Spiffy\Event\EventsAwareTrait;
use Spiffy\Event\Manager as EventManager;
use Spiffy\Package\Feature;
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
    protected $mergedConfig = [];

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
                $this->mergedConfig = array_replace_recursive($this->mergedConfig, $package->getConfig());
            }
        }
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

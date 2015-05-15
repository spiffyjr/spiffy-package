<?php
namespace Spiffy\Package;

use Spiffy\Package\Feature\ConfigProviderInterface;

class DefaultPackageHook implements PackageHookInterface
{
    /**
     * {@inheritDoc}
     */
    public function onLoad(PackageManager $packageManager)
    {
        $packages = $packageManager->getPackages();
        foreach ($packages as $name => $package) {
            if (empty($package)) {
                $packageName = $name . '\\Package';
                $package = class_exists($packageName) ? new $packageName() : null;
            }

            if (null === $package) {
                throw new Exception\PackageLoadFailedException($name);
            }

            $packages[$name] = $package;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onMerge(PackageManager $packageManager)
    {
        $config = [];

        foreach ($packageManager->getPackages() as $package) {
            if ($package instanceof ConfigProviderInterface) {
                $config = $packageManager->merge($config, $package->getConfig());
            }
        }

        $pmConfig = $packageManager->getConfig();
        if (null === $pmConfig['override_pattern']) {
            return $config;
        }

        $overrideFiles = glob($pmConfig['override_pattern'], $pmConfig['override_flags']);
        foreach ($overrideFiles as $file) {
            $config = $packageManager->merge($config, include $file);
        }

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    public function afterLoad(PackageManager $packageManager, array $mergedConfig)
    {
    }
}

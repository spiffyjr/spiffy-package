<?php
namespace Spiffy\Package\TestAsset;

use Spiffy\Package\PackageHook;
use Spiffy\Package\PackageManager;

class TestHook implements PackageHook
{
    public $onLoad = false;
    public $mergedConfig = [];

    public function onLoad(PackageManager $packageManager)
    {
        $this->onLoad = true;
    }

    public function afterLoad(PackageManager $packageManager, array $mergedConfig)
    {
        $this->mergedConfig = $mergedConfig;
    }

    public function onMerge(PackageManager $packageManager)
    {
        return ['foo' => 'bar'];
    }
}

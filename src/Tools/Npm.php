<?php

namespace Laravel\Chisel\Tools;

use Illuminate\Process\Factory;
use Laravel\Chisel\NodePackageManager;

class Npm
{
    public function __construct(protected string $directory) {}

    public function run(string $script): void
    {
        $packageManager = NodePackageManager::detect($this->directory);

        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($packageManager->runProcessCommand($script))
            ->throw();
    }

    public function remove(string ...$packages): void
    {
        $packageManager = NodePackageManager::detect($this->directory);

        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($packageManager->removeProcessCommand(...$packages))
            ->throw();
    }
}

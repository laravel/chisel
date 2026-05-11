<?php

namespace Laravel\Chisel\Tools;

use Illuminate\Process\Factory;
use Laravel\Chisel\NodePackageManager;

class Npm
{
    protected ?NodePackageManager $packageManager = null;

    public function __construct(protected string $directory)
    {
        //
    }

    public function run(string $script): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($this->packageManager()->runProcessCommand($script))
            ->throw();
    }

    public function remove(string ...$packages): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($this->packageManager()->removeProcessCommand(...$packages))
            ->throw();
    }

    public function packageManager(): NodePackageManager
    {
        return $this->packageManager ??= self::detectFromLockFile()
            ?? self::detectFromComposerScripts()
            ?? NodePackageManager::NPM;
    }

    protected function detectFromLockFile(): ?NodePackageManager
    {
        foreach (NodePackageManager::nonNpmManagers() as $packageManager) {
            foreach ($packageManager->lockFiles() as $lockFile) {
                if (file_exists($this->directory.'/'.$lockFile)) {
                    return $packageManager;
                }
            }
        }

        return null;
    }

    protected function detectFromComposerScripts(): ?NodePackageManager
    {
        $composerJson = $this->directory.'/composer.json';

        if (! file_exists($composerJson)) {
            return null;
        }

        $composer = json_decode(file_get_contents($composerJson), true);
        $scripts = $composer['scripts'] ?? null;

        if (! is_array($scripts)) {
            return null;
        }

        foreach (['dev', 'dev:ssr', 'setup'] as $script) {
            foreach ((array) ($scripts[$script] ?? []) as $command) {
                if (! is_string($command)) {
                    continue;
                }

                foreach (NodePackageManager::nonNpmManagers() as $packageManager) {
                    $pattern = '/(^|[^[:alnum:]_-])'.preg_quote($packageManager->value, '/').'(?=\s|$)/';

                    if (preg_match($pattern, $command) === 1) {
                        return $packageManager;
                    }
                }
            }
        }

        return null;
    }
}

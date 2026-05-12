<?php

namespace Laravel\Chisel\Node;

use Illuminate\Process\Factory;

class Npm
{
    protected ?PackageManager $packageManager = null;

    public function __construct(protected string $directory)
    {
        //
    }

    public function install(): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($this->packageManager()->installCommand())
            ->throw();
    }

    public function run(string $script, string ...$arguments): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($this->packageManager()->runCommand($script, ...$arguments))
            ->throw();
    }

    public function remove(string ...$packages): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($this->packageManager()->removeCommand(...$packages))
            ->throw();
    }

    public function packageManager(): PackageManager
    {
        return $this->packageManager ??= $this->detectFromLockFile()
            ?? $this->detectFromComposerScripts()
            ?? PackageManager::NPM;
    }

    protected function detectFromLockFile(): ?PackageManager
    {
        foreach (PackageManager::nonNpmManagers() as $packageManager) {
            foreach ($packageManager->lockFiles() as $lockFile) {
                if (file_exists($this->directory.'/'.$lockFile)) {
                    return $packageManager;
                }
            }
        }

        return null;
    }

    protected function detectFromComposerScripts(): ?PackageManager
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

                foreach (PackageManager::nonNpmManagers() as $packageManager) {
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

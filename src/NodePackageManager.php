<?php

namespace Laravel\Chisel;

enum NodePackageManager: string
{
    case NPM = 'npm';
    case YARN = 'yarn';
    case PNPM = 'pnpm';
    case BUN = 'bun';

    public static function detect(string $directory): self
    {
        return self::detectFromLockFile($directory) ?? self::detectFromComposerScripts($directory) ?? self::NPM;
    }

    public function installCommand(): string
    {
        return implode(' ', $this->installProcessCommand());
    }

    /**
     * @return list<string>
     */
    public function installProcessCommand(): array
    {
        return match ($this) {
            self::NPM => ['npm', 'install'],
            self::YARN => ['yarn', 'install'],
            self::PNPM => ['pnpm', 'install'],
            self::BUN => ['bun', 'install'],
        };
    }

    public function buildCommand(): string
    {
        return implode(' ', $this->buildProcessCommand());
    }

    /**
     * @return list<string>
     */
    public function buildProcessCommand(): array
    {
        return match ($this) {
            self::NPM => ['npm', 'run', 'build'],
            self::YARN => ['yarn', 'build'],
            self::PNPM => ['pnpm', 'build'],
            self::BUN => ['bun', 'run', 'build'],
        };
    }

    public function runCommand(string $script): string
    {
        return implode(' ', $this->runProcessCommand($script));
    }

    /**
     * @return list<string>
     */
    public function runProcessCommand(string $script): array
    {
        return match ($this) {
            self::NPM => ['npm', 'run', $script],
            self::YARN => ['yarn', $script],
            self::PNPM => ['pnpm', $script],
            self::BUN => ['bun', 'run', $script],
        };
    }

    public function removeCommand(string ...$packages): string
    {
        return implode(' ', $this->removeProcessCommand(...$packages));
    }

    /**
     * @return list<string>
     */
    public function removeProcessCommand(string ...$packages): array
    {
        return match ($this) {
            self::NPM => ['npm', 'remove', ...$packages],
            self::YARN => ['yarn', 'remove', ...$packages],
            self::PNPM => ['pnpm', 'remove', ...$packages],
            self::BUN => ['bun', 'remove', ...$packages],
        };
    }

    private static function detectFromLockFile(string $directory): ?self
    {
        foreach (self::nonNpmManagers() as $packageManager) {
            foreach ($packageManager->lockFiles() as $lockFile) {
                if (file_exists($directory.'/'.$lockFile)) {
                    return $packageManager;
                }
            }
        }

        return null;
    }

    private static function detectFromComposerScripts(string $directory): ?self
    {
        $composerJson = $directory.'/composer.json';

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

                foreach (self::nonNpmManagers() as $packageManager) {
                    $pattern = '/(^|[^[:alnum:]_-])'.preg_quote($packageManager->value, '/').'(?=\s|$)/';

                    if (preg_match($pattern, $command) === 1) {
                        return $packageManager;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return list<self>
     */
    private static function nonNpmManagers(): array
    {
        return array_values(array_filter(self::cases(), fn (self $packageManager) => $packageManager !== self::NPM));
    }

    private function lockFiles(): array
    {
        return match ($this) {
            self::NPM => ['package-lock.json'],
            self::YARN => ['yarn.lock'],
            self::PNPM => ['pnpm-lock.yaml'],
            self::BUN => ['bun.lock', 'bun.lockb'],
        };
    }
}

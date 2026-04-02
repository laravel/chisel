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
        foreach (self::cases() as $packageManager) {
            if ($packageManager === self::NPM) {
                continue;
            }

            foreach ($packageManager->lockFiles() as $lockFile) {
                if (file_exists($directory.'/'.$lockFile)) {
                    return $packageManager;
                }
            }
        }

        return self::detectFromComposerScripts($directory) ?? self::NPM;
    }

    public function installCommand(): string
    {
        return match ($this) {
            self::NPM => 'npm install',
            self::YARN => 'yarn install',
            self::PNPM => 'pnpm install',
            self::BUN => 'bun install',
        };
    }

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
        return match ($this) {
            self::NPM => 'npm run build',
            self::YARN => 'yarn build',
            self::PNPM => 'pnpm build',
            self::BUN => 'bun run build',
        };
    }

    public function buildProcessCommand(): array
    {
        return match ($this) {
            self::NPM => ['npm', 'run', 'build'],
            self::YARN => ['yarn', 'build'],
            self::PNPM => ['pnpm', 'build'],
            self::BUN => ['bun', 'run', 'build'],
        };
    }

    public function removeProcessCommand(string ...$packages): array
    {
        return match ($this) {
            self::NPM => ['npm', 'remove', ...$packages],
            self::YARN => ['yarn', 'remove', ...$packages],
            self::PNPM => ['pnpm', 'remove', ...$packages],
            self::BUN => ['bun', 'remove', ...$packages],
        };
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

                foreach ([self::YARN, self::PNPM, self::BUN] as $packageManager) {
                    $pattern = '/(^|[^[:alnum:]_-])'.preg_quote($packageManager->value, '/').'(?=\s|$)/';

                    if (preg_match($pattern, $command) === 1) {
                        return $packageManager;
                    }
                }
            }
        }

        return null;
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

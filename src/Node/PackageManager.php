<?php

namespace Laravel\Chisel\Node;

enum PackageManager: string
{
    case NPM = 'npm';
    case YARN = 'yarn';
    case PNPM = 'pnpm';
    case BUN = 'bun';

    /**
     * @return list<self>
     */
    public static function nonNpmManagers(): array
    {
        return array_values(array_filter(self::cases(), fn (self $packageManager): bool => $packageManager !== self::NPM));
    }

    /**
     * @return list<string>
     */
    public function installCommand(): array
    {
        return match ($this) {
            self::NPM => ['npm', 'install'],
            self::YARN => ['yarn', 'install'],
            self::PNPM => ['pnpm', 'install'],
            self::BUN => ['bun', 'install'],
        };
    }

    /**
     * @return list<string>
     */
    public function runCommand(string $script, string ...$arguments): array
    {
        return match ($this) {
            self::NPM => ['npm', 'run', $script, ...($arguments === [] ? [] : ['--']), ...$arguments],
            self::YARN => ['yarn', $script, ...$arguments],
            self::PNPM => ['pnpm', $script, ...$arguments],
            self::BUN => ['bun', 'run', $script, ...$arguments],
        };
    }

    /**
     * @return list<string>
     */
    public function removeCommand(string ...$packages): array
    {
        return match ($this) {
            self::NPM => ['npm', 'remove', ...$packages],
            self::YARN => ['yarn', 'remove', ...$packages],
            self::PNPM => ['pnpm', 'remove', ...$packages],
            self::BUN => ['bun', 'remove', ...$packages],
        };
    }

    /**
     * @return list<string>
     */
    public function lockFiles(): array
    {
        return match ($this) {
            self::NPM => ['package-lock.json'],
            self::YARN => ['yarn.lock'],
            self::PNPM => ['pnpm-lock.yaml'],
            self::BUN => ['bun.lock', 'bun.lockb'],
        };
    }
}

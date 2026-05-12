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

    public function installCommand(): string
    {
        return implode(' ', $this->installProcessCommand());
    }

    public function buildCommand(): string
    {
        return implode(' ', $this->buildProcessCommand());
    }

    public function runCommand(string $script): string
    {
        return implode(' ', $this->runProcessCommand($script));
    }

    public function removeCommand(string ...$packages): string
    {
        return implode(' ', $this->removeProcessCommand(...$packages));
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

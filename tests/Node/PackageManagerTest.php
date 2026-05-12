<?php

use Laravel\Chisel\Node\PackageManager;

dataset('package-managers', [
    'npm' => [
        PackageManager::NPM,
        'npm install',
        ['npm', 'install'],
        'npm run build',
        ['npm', 'run', 'build'],
        'npm run lint',
        ['npm', 'run', 'lint'],
        ['npm', 'remove', 'vite'],
    ],
    'yarn' => [
        PackageManager::YARN,
        'yarn install',
        ['yarn', 'install'],
        'yarn build',
        ['yarn', 'build'],
        'yarn lint',
        ['yarn', 'lint'],
        ['yarn', 'remove', 'vite'],
    ],
    'pnpm' => [
        PackageManager::PNPM,
        'pnpm install',
        ['pnpm', 'install'],
        'pnpm build',
        ['pnpm', 'build'],
        'pnpm lint',
        ['pnpm', 'lint'],
        ['pnpm', 'remove', 'vite'],
    ],
    'bun' => [
        PackageManager::BUN,
        'bun install',
        ['bun', 'install'],
        'bun run build',
        ['bun', 'run', 'build'],
        'bun run lint',
        ['bun', 'run', 'lint'],
        ['bun', 'remove', 'vite'],
    ],
]);

it('returns the expected commands for each package manager', function (
    PackageManager $packageManager,
    string $installCommand,
    array $installProcessCommand,
    string $buildCommand,
    array $buildProcessCommand,
    string $runCommand,
    array $runProcessCommand,
    array $removeProcessCommand,
): void {
    expect($packageManager->installCommand())->toBe($installCommand)
        ->and($packageManager->installProcessCommand())->toBe($installProcessCommand)
        ->and($packageManager->buildCommand())->toBe($buildCommand)
        ->and($packageManager->buildProcessCommand())->toBe($buildProcessCommand)
        ->and($packageManager->runCommand('lint'))->toBe($runCommand)
        ->and($packageManager->runProcessCommand('lint'))->toBe($runProcessCommand)
        ->and($packageManager->removeProcessCommand('vite'))->toBe($removeProcessCommand);
})->with('package-managers');

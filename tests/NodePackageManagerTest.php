<?php

use Laravel\Chisel\NodePackageManager;

dataset('package-managers', [
    'npm' => [
        NodePackageManager::NPM,
        'npm install',
        ['npm', 'install'],
        'npm run build',
        ['npm', 'run', 'build'],
        'npm run lint',
        ['npm', 'run', 'lint'],
        ['npm', 'remove', 'vite'],
    ],
    'yarn' => [
        NodePackageManager::YARN,
        'yarn install',
        ['yarn', 'install'],
        'yarn build',
        ['yarn', 'build'],
        'yarn lint',
        ['yarn', 'lint'],
        ['yarn', 'remove', 'vite'],
    ],
    'pnpm' => [
        NodePackageManager::PNPM,
        'pnpm install',
        ['pnpm', 'install'],
        'pnpm build',
        ['pnpm', 'build'],
        'pnpm lint',
        ['pnpm', 'lint'],
        ['pnpm', 'remove', 'vite'],
    ],
    'bun' => [
        NodePackageManager::BUN,
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
    NodePackageManager $packageManager,
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

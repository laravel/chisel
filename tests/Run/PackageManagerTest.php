<?php

use Laravel\Chisel\Run\PackageManager;

dataset('package-managers', [
    'npm' => [
        PackageManager::NPM,
        ['npm', 'install'],
        ['npm', 'run', 'lint'],
        ['npm', 'run', 'lint', '--', '--fix'],
        ['npm', 'remove', 'vite'],
    ],
    'yarn' => [
        PackageManager::YARN,
        ['yarn', 'install'],
        ['yarn', 'lint'],
        ['yarn', 'lint', '--fix'],
        ['yarn', 'remove', 'vite'],
    ],
    'pnpm' => [
        PackageManager::PNPM,
        ['pnpm', 'install'],
        ['pnpm', 'lint'],
        ['pnpm', 'lint', '--fix'],
        ['pnpm', 'remove', 'vite'],
    ],
    'bun' => [
        PackageManager::BUN,
        ['bun', 'install'],
        ['bun', 'run', 'lint'],
        ['bun', 'run', 'lint', '--fix'],
        ['bun', 'remove', 'vite'],
    ],
]);

it('returns the expected commands for each package manager', function (
    PackageManager $packageManager,
    array $installCommand,
    array $runCommand,
    array $runCommandWithArguments,
    array $removeCommand,
): void {
    expect($packageManager->installCommand())->toBe($installCommand)
        ->and($packageManager->runCommand('lint'))->toBe($runCommand)
        ->and($packageManager->runCommand('lint', '--fix'))->toBe($runCommandWithArguments)
        ->and($packageManager->removeCommand('vite'))->toBe($removeCommand);
})->with('package-managers');

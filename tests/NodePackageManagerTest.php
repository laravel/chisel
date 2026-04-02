<?php

use Laravel\Chisel\NodePackageManager;

beforeEach(function (): void {
    $this->tempDir = __DIR__.'/../tests-output/package-manager-'.uniqid();

    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    if (! file_exists($this->tempDir)) {
        return;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        system("rd /s /q \"{$this->tempDir}\"");

        return;
    }

    system("rm -rf \"{$this->tempDir}\"");
});

dataset('package-managers', [
    'npm' => [
        NodePackageManager::NPM,
        'npm install',
        ['npm', 'install'],
        'npm run build',
        ['npm', 'run', 'build'],
        ['npm', 'remove', 'vite'],
    ],
    'yarn' => [
        NodePackageManager::YARN,
        'yarn install',
        ['yarn', 'install'],
        'yarn build',
        ['yarn', 'build'],
        ['yarn', 'remove', 'vite'],
    ],
    'pnpm' => [
        NodePackageManager::PNPM,
        'pnpm install',
        ['pnpm', 'install'],
        'pnpm build',
        ['pnpm', 'build'],
        ['pnpm', 'remove', 'vite'],
    ],
    'bun' => [
        NodePackageManager::BUN,
        'bun install',
        ['bun', 'install'],
        'bun run build',
        ['bun', 'run', 'build'],
        ['bun', 'remove', 'vite'],
    ],
]);

it('returns the expected commands for each package manager', function (
    NodePackageManager $packageManager,
    string $installCommand,
    array $installProcessCommand,
    string $buildCommand,
    array $buildProcessCommand,
    array $removeProcessCommand,
): void {
    expect($packageManager->installCommand())->toBe($installCommand)
        ->and($packageManager->installProcessCommand())->toBe($installProcessCommand)
        ->and($packageManager->buildCommand())->toBe($buildCommand)
        ->and($packageManager->buildProcessCommand())->toBe($buildProcessCommand)
        ->and($packageManager->removeProcessCommand('vite'))->toBe($removeProcessCommand);
})->with('package-managers');

dataset('package-manager-lock-files', [
    'yarn' => ['yarn.lock', NodePackageManager::YARN],
    'pnpm' => ['pnpm-lock.yaml', NodePackageManager::PNPM],
    'bun lock' => ['bun.lock', NodePackageManager::BUN],
    'bun lockb' => ['bun.lockb', NodePackageManager::BUN],
]);

it('detects the package manager from lock files', function (string $lockFile, NodePackageManager $packageManager): void {
    file_put_contents($this->tempDir.'/'.$lockFile, '');

    expect(NodePackageManager::detect($this->tempDir))->toBe($packageManager);
})->with('package-manager-lock-files');

dataset('composer-script-managers', [
    'yarn' => ['yarn run dev', NodePackageManager::YARN],
    'pnpm' => ['pnpm dev', NodePackageManager::PNPM],
    'bun' => ['bun run dev', NodePackageManager::BUN],
]);

it('detects the package manager from composer scripts when no lock file exists', function (string $script, NodePackageManager $packageManager): void {
    file_put_contents($this->tempDir.'/composer.json', json_encode([
        'scripts' => [
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                $script,
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect(NodePackageManager::detect($this->tempDir))->toBe($packageManager);
})->with('composer-script-managers');

it('defaults to npm when no package manager hint exists', function (): void {
    expect(NodePackageManager::detect($this->tempDir))->toBe(NodePackageManager::NPM);
});

it('defaults to npm when composer scripts are missing', function (): void {
    file_put_contents($this->tempDir.'/composer.json', json_encode([], JSON_THROW_ON_ERROR));

    expect(NodePackageManager::detect($this->tempDir))->toBe(NodePackageManager::NPM);
});

it('ignores non-string composer script entries while detecting the package manager', function (): void {
    file_put_contents($this->tempDir.'/composer.json', json_encode([
        'scripts' => [
            'dev' => [
                ['bun run dev'],
                'npm run dev',
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect(NodePackageManager::detect($this->tempDir))->toBe(NodePackageManager::NPM);
});

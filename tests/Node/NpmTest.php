<?php

use Laravel\Chisel\Chisel;

function shimBinary(string $tempDir, string $binary): string
{
    $bin = $tempDir.'/bin';
    $log = $tempDir.'/'.$binary.'.log';

    if (! is_dir($bin)) {
        mkdir($bin, 0777, true);
    }

    file_put_contents($bin.'/'.$binary, "#!/bin/sh\nprintf '%s\n' \"$(pwd)|$*\" > \"$log\"\n");
    chmod($bin.'/'.$binary, 0755);

    return $log;
}

function withShimmedPath(string $tempDir, Closure $callback): void
{
    $originalPath = getenv('PATH') ?: '';
    putenv('PATH='.$tempDir.'/bin:'.$originalPath);

    try {
        $callback();
    } finally {
        putenv('PATH='.$originalPath);
    }
}

dataset('npm-remove-commands', [
    'defaults to npm' => [null, 'npm'],
    'uses pnpm when lock file exists' => ['pnpm-lock.yaml', 'pnpm'],
]);

it('runs package manager remove in the project directory', function (?string $lockFile, string $binary): void {
    if ($lockFile !== null) {
        file_put_contents($this->tempDir.'/'.$lockFile, '');
    }

    $log = shimBinary($this->tempDir, $binary);

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->npm()->remove('@laravel/passkeys', 'input-otp'));

    expect(file_get_contents($log))
        ->toContain(realpath($this->tempDir))
        ->toContain('remove @laravel/passkeys input-otp');
})->with('npm-remove-commands');

dataset('npm-run-commands', [
    'defaults to npm' => [null, 'npm'],
    'uses yarn when lock file exists' => ['yarn.lock', 'yarn'],
    'uses bun when lock file exists' => ['bun.lockb', 'bun'],
]);

it('runs package manager scripts in the project directory', function (?string $lockFile, string $binary): void {
    if ($lockFile !== null) {
        file_put_contents($this->tempDir.'/'.$lockFile, '');
    }

    $log = shimBinary($this->tempDir, $binary);

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->npm()->run('lint'));

    expect(file_get_contents($log))
        ->toContain(realpath($this->tempDir))
        ->toContain('lint');
})->with('npm-run-commands');

dataset('lock-file-detection', [
    'yarn.lock' => ['yarn.lock', 'yarn'],
    'pnpm-lock.yaml' => ['pnpm-lock.yaml', 'pnpm'],
    'bun.lock' => ['bun.lock', 'bun'],
    'bun.lockb' => ['bun.lockb', 'bun'],
]);

it('detects the package manager from lock files', function (string $lockFile, string $binary): void {
    file_put_contents($this->tempDir.'/'.$lockFile, '');

    $log = shimBinary($this->tempDir, $binary);

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->npm()->remove('vite'));

    expect(file_get_contents($log))->toContain('remove vite');
})->with('lock-file-detection');

dataset('composer-script-detection', [
    'yarn' => ['yarn run dev', 'yarn'],
    'pnpm' => ['pnpm dev', 'pnpm'],
    'bun' => ['bun run dev', 'bun'],
]);

it('detects the package manager from composer scripts when no lock file exists', function (string $script, string $binary): void {
    file_put_contents($this->tempDir.'/composer.json', json_encode([
        'scripts' => [
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                $script,
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    $log = shimBinary($this->tempDir, $binary);

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->npm()->remove('vite'));

    expect(file_get_contents($log))->toContain('remove vite');
})->with('composer-script-detection');

it('defaults to npm when composer scripts are missing', function (): void {
    file_put_contents($this->tempDir.'/composer.json', json_encode([], JSON_THROW_ON_ERROR));

    $log = shimBinary($this->tempDir, 'npm');

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->npm()->remove('vite'));

    expect(file_get_contents($log))->toContain('remove vite');
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

    $log = shimBinary($this->tempDir, 'npm');

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->npm()->remove('vite'));

    expect(file_get_contents($log))->toContain('remove vite');
});

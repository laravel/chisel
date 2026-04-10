<?php

use Laravel\Chisel\Chisel;

dataset('npm-remove-commands', [
    'defaults to npm' => [null, 'npm'],
    'uses pnpm when lock file exists' => ['pnpm-lock.yaml', 'pnpm'],
]);

it('runs package manager remove in the project directory', function (?string $lockFile, string $binary): void {
    $bin = $this->tempDir.'/bin';
    $log = $this->tempDir.'/'.$binary.'.log';

    mkdir($bin, 0777, true);

    if ($lockFile !== null) {
        file_put_contents($this->tempDir.'/'.$lockFile, '');
    }

    file_put_contents($bin.'/'.$binary, "#!/bin/sh\nprintf '%s\n' \"$(pwd)|$*\" > \"$log\"\n");
    chmod($bin.'/'.$binary, 0755);

    $originalPath = getenv('PATH') ?: '';
    putenv('PATH='.$bin.':'.$originalPath);

    try {
        Chisel::in($this->tempDir)->npm()->remove('@laravel/passkeys', 'input-otp');
    } finally {
        putenv('PATH='.$originalPath);
    }

    expect(file_get_contents($log))
        ->toContain(realpath($this->tempDir))
        ->toContain('remove @laravel/passkeys input-otp');
})->with('npm-remove-commands');

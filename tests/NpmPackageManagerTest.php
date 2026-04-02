<?php

use Laravel\Chisel\Chisel;

beforeEach(function (): void {
    $this->tempDir = __DIR__.'/../tests-output/npm-package-manager-'.uniqid();

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

it('removes packages with the detected package manager', function (): void {
    $bin = $this->tempDir.'/bin';
    $log = $this->tempDir.'/pnpm.log';

    mkdir($bin, 0777, true);
    file_put_contents($this->tempDir.'/pnpm-lock.yaml', '');
    file_put_contents($bin.'/pnpm', "#!/bin/sh\nprintf '%s\n' \"$(pwd)|$*\" > \"$log\"\n");
    chmod($bin.'/pnpm', 0755);

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
});

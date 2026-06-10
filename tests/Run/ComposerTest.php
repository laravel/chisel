<?php

use Laravel\Chisel\Chisel;

it('removes composer packages in the project directory', function (): void {
    $log = shimBinary($this->tempDir, 'composer');

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->composer()->remove('laravel/chisel'));

    expect(file_get_contents($log))
        ->toContain(realpath($this->tempDir))
        ->toContain('remove laravel/chisel --no-interaction');
});

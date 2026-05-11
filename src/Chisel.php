<?php

namespace Laravel\Chisel;

use Laravel\Chisel\Tools\File;
use Laravel\Chisel\Tools\Npm;
use Laravel\Chisel\Tools\Php\PhpFile;

/** @phpstan-consistent-constructor */
class Chisel
{
    protected function __construct(protected string $directory)
    {
        //
    }

    public static function in(string $directory): static
    {
        return new static($directory);
    }

    public static function script(string $directory): Script
    {
        return new Script($directory);
    }

    public function files(string ...$paths): PendingFiles
    {
        return new PendingFiles(new File($this->directory), $paths);
    }

    public function file(string $path): PendingFiles
    {
        return $this->files($path);
    }

    public function npm(): Npm
    {
        return new Npm($this->directory);
    }

    public function phpFile(string $path): PhpFile
    {
        return new PhpFile($this->path($path));
    }

    private function path(string $path): string
    {
        return $this->directory.'/'.$path;
    }
}

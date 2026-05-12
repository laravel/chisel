<?php

namespace Laravel\Chisel;

use Laravel\Chisel\Ast\Source;
use Laravel\Chisel\Filesystem\File;
use Laravel\Chisel\Filesystem\PendingFiles;
use Laravel\Chisel\Node\Npm;

/** @phpstan-consistent-constructor */
class Chisel
{
    protected ?Npm $npm = null;

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
        return $this->npm ??= new Npm($this->directory);
    }

    public function php(string $path): Source
    {
        return new Source($this->path($path));
    }

    private function path(string $path): string
    {
        return $this->directory.'/'.$path;
    }
}

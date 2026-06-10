<?php

namespace Laravel\Chisel;

use Illuminate\Process\Factory;
use Laravel\Chisel\Ast\Source;
use Laravel\Chisel\Filesystem\File;
use Laravel\Chisel\Filesystem\PendingFiles;
use Laravel\Chisel\Run\Composer;
use Laravel\Chisel\Run\Npm;

/** @phpstan-consistent-constructor */
class Chisel
{
    protected ?Composer $composer = null;

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

    public function composer(): Composer
    {
        return $this->composer ??= new Composer($this);
    }

    public function npm(): Npm
    {
        return $this->npm ??= new Npm($this);
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function php(string $path): Source
    {
        return new Source($this->path($path));
    }

    /**
     * @param  list<string>  $command
     */
    public function run(array $command): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($command)
            ->throw();
    }

    private function path(string $path): string
    {
        return $this->directory.'/'.$path;
    }
}

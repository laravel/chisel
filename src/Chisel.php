<?php

namespace Laravel\Chisel;

use Laravel\Chisel\Tools\File;
use Laravel\Chisel\Tools\Npm;
use Laravel\Chisel\Tools\Php\PhpFile;

use function Laravel\Prompts\multiselect as promptMultiselect;

/** @phpstan-consistent-constructor */
class Chisel
{
    protected array $answers = [];

    protected function __construct(protected string $directory) {}

    public static function in(string $directory): static
    {
        return new static($directory);
    }

    public function withAnswers(?string $json): static
    {
        if ($json !== null) {
            $this->answers = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this;
    }

    public function multiselect(string $name, string $label, array $options, array $default = [], string $hint = '', bool|string $required = false): static
    {
        $this->answers[$name] ??= promptMultiselect($label, $options, $default, required: $required, hint: $hint);

        return $this;
    }

    public function selected(string $key, string $value, ?callable $then = null, ?callable $else = null): static
    {
        if (in_array($value, $this->answer($key, []))) {
            if ($then) {
                $then($this);
            }
        } elseif ($else) {
            $else($this);
        }

        return $this;
    }

    public function selectedAny(string $key, array $values, ?callable $then = null, ?callable $else = null): static
    {
        $selected = (array) $this->answer($key, []);

        foreach ($values as $value) {
            if (in_array($value, $selected, true)) {
                if ($then) {
                    $then($this);
                }

                return $this;
            }
        }

        if ($else) {
            $else($this);
        }

        return $this;
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

    private function answer(string $key, mixed $default = null): mixed
    {
        return $this->answers[$key] ?? $default;
    }
}

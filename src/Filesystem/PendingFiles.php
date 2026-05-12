<?php

namespace Laravel\Chisel\Filesystem;

class PendingFiles
{
    /**
     * @param  array<string>  $paths
     */
    public function __construct(
        protected File $file,
        protected array $paths,
    ) {
        //
    }

    public function delete(): static
    {
        $this->file->delete(...$this->paths);

        return $this;
    }

    public function replace(string $search, string $replace): static
    {
        foreach ($this->paths as $path) {
            $this->file->replace($path, $search, $replace);
        }

        return $this;
    }

    public function removeLinesContaining(string $content): static
    {
        foreach ($this->paths as $path) {
            $this->file->removeLinesContaining($path, $content);
        }

        return $this;
    }

    public function removeSection(string $tag): static
    {
        foreach ($this->paths as $path) {
            $this->file->removeSection($path, $tag);
        }

        return $this;
    }

    public function removeSectionMarkers(string $tag): static
    {
        foreach ($this->paths as $path) {
            $this->file->removeSectionMarkers($path, $tag);
        }

        return $this;
    }
}

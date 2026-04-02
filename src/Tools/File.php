<?php

namespace Laravel\Chisel\Tools;

class File
{
    public function __construct(protected string $directory) {}

    public function delete(string ...$paths): void
    {
        foreach ($paths as $path) {
            $fullPath = $this->directory.'/'.$path;

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function replace(string $file, string $search, string $replace): void
    {
        if (! $this->exists($file)) {
            return;
        }

        $this->write($file, str_replace($search, $replace, $this->read($file)));
    }

    public function removeLinesContaining(string $file, string $content): void
    {
        if (! $this->exists($file)) {
            return;
        }

        $lines = explode("\n", $this->read($file));
        $lines = array_values(array_filter($lines, fn (string $line): bool => ! str_contains($line, $content)));

        $this->write($file, implode("\n", $lines));
    }

    public function removeSectionMarkers(string $file, string $tag): void
    {
        $this->rewriteSection($file, $tag, keepContents: true);
    }

    public function removeSection(string $file, string $tag): void
    {
        $this->rewriteSection($file, $tag, keepContents: false);
    }

    /**
     * @return array{start: array<int, string>, end: array<int, string>}
     */
    protected function blockPatterns(string $tag): array
    {
        $escapedTag = preg_quote($tag, '/');

        return [
            'start' => [
                '/^\s*\{?\/\*\s*@'.$escapedTag.'\s*\*\/\}?\s*$/',
                '/^\s*<!--\s*@'.$escapedTag.'\s*-->\s*$/',
                '/^\s*\{\{--\s*@'.$escapedTag.'\s*--\}\}\s*$/',
            ],
            'end' => [
                '/^\s*\{?\/\*\s*@end-'.$escapedTag.'\s*\*\/\}?\s*$/',
                '/^\s*<!--\s*@end-'.$escapedTag.'\s*-->\s*$/',
                '/^\s*\{\{--\s*@end-'.$escapedTag.'\s*--\}\}\s*$/',
            ],
        ];
    }

    protected function rewriteSection(string $file, string $tag, bool $keepContents): void
    {
        if (! $this->exists($file)) {
            return;
        }

        $lines = explode("\n", $this->read($file));
        ['start' => $startPatterns, 'end' => $endPatterns] = $this->blockPatterns($tag);

        $result = [];
        $inBlock = false;

        foreach ($lines as $line) {
            if ($this->matchesAnyPattern($line, $startPatterns)) {
                $inBlock = true;

                continue;
            }

            if ($inBlock && $this->matchesAnyPattern($line, $endPatterns)) {
                $inBlock = false;

                continue;
            }

            if ($keepContents || ! $inBlock) {
                $result[] = $line;
            }
        }

        $this->write($file, implode("\n", $result));
    }

    /**
     * @param  array<int, string>  $patterns
     */
    protected function matchesAnyPattern(string $line, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    protected function read(string $file): string
    {
        return file_get_contents($this->directory.'/'.$file);
    }

    protected function write(string $file, string $contents): void
    {
        file_put_contents($this->directory.'/'.$file, $contents);
    }

    protected function exists(string $file): bool
    {
        return file_exists($this->directory.'/'.$file);
    }
}

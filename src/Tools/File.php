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

    protected function rewriteSection(string $file, string $tag, bool $keepContents): void
    {
        if (! $this->exists($file)) {
            return;
        }

        $content = $this->read($file);
        $escapedTag = preg_quote($tag, '/');

        $styles = [
            ['\{?\/\*', '\*\/\}?'],
            ['<!--', '-->'],
            ['\{\{--', '--\}\}'],
        ];

        foreach ($styles as [$open, $close]) {
            $start = $open.'\s*@'.$escapedTag.'\s*'.$close;
            $end = $open.'\s*@end-'.$escapedTag.'\s*'.$close;

            if ($keepContents) {
                // Drop marker-only lines first, then handle inline markers.
                $content = preg_replace('/^\h*'.$start.'\h*\R?/m', '', $content);
                $content = preg_replace('/^\h*'.$end.'\h*\R?/m', '', $content);
                $content = preg_replace('/'.$start.'\h*/', '', $content);
                $content = preg_replace('/\h*'.$end.'/', '', $content);
            } else {
                // Remove full blocks, including marker-only multi-line sections and inline sections.
                $content = preg_replace('/^\h*'.$start.'\h*\R.*?^\h*'.$end.'\h*(?:\R|$)/ms', '', $content);
                $content = preg_replace('/'.$start.'.*?'.$end.'\h*/s', '', $content);
            }
        }

        $this->write($file, $content);
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

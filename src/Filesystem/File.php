<?php

namespace Laravel\Chisel\Filesystem;

class File
{
    public function __construct(protected string $directory)
    {
        //
    }

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

        $tag = $this->normalizeTag($tag);
        $content = $this->read($file);
        $escapedTag = preg_quote($tag, '/');

        $styles = [
            ['\{?\/\*', '\*\/\}?'],
            ['<!--', '-->'],
            ['\{\{--', '--\}\}'],
        ];

        $this->validateMarkers($content, $file, $tag, $styles, $escapedTag);

        foreach ($styles as [$open, $close]) {
            $start = $open.'\s*@'.$escapedTag.'\s*'.$close;
            $end = $open.'\s*@end-'.$escapedTag.'\s*'.$close;

            if ($keepContents) {
                // Drop marker-only lines first, then handle inline markers.
                $content = preg_replace('/^\h*'.$start.'\h*\R?/m', '', (string) $content);
                $content = preg_replace('/^\h*'.$end.'\h*\R?/m', '', (string) $content);
                $content = preg_replace('/'.$start.'\h*/', '', (string) $content);
                $content = preg_replace('/\h*'.$end.'/', '', (string) $content);
            } else {
                // Remove full blocks, including marker-only multi-line sections and inline sections.
                $content = preg_replace('/^\h*'.$start.'\h*\R.*?^\h*'.$end.'\h*(?:\R|$)/ms', '', (string) $content);
                $content = preg_replace('/'.$start.'.*?'.$end.'\h*/s', '', (string) $content);
            }
        }

        $this->write($file, $content);
    }

    /**
     * @param  list<array{string, string}>  $styles
     */
    protected function validateMarkers(string $content, string $file, string $tag, array $styles, string $escapedTag): void
    {
        $markers = [];

        foreach ($styles as [$open, $close]) {
            preg_match_all('/'.$open.'\s*@(?:end-)?'.$escapedTag.'\s*'.$close.'/', $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $isEnd = (bool) preg_match('/@end-/', $match);
                $markers[] = ['offset' => $offset, 'end' => $isEnd];
            }
        }

        usort($markers, fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        $counter = count($markers);

        for ($i = 1; $i < $counter; $i++) {
            if ($markers[$i]['end'] === $markers[$i - 1]['end']) {
                $type = $markers[$i]['end'] ? 'closing' : 'opening';

                throw new \RuntimeException("Consecutive {$type} markers for @{$tag} in {$file}.");
            }
        }
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

    protected function normalizeTag(string $tag): string
    {
        return str_starts_with($tag, 'chisel-') ? $tag : 'chisel-'.$tag;
    }
}

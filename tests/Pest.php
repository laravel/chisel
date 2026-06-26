<?php

pest()->beforeEach(function (): void {
    $this->tempDir = __DIR__.'/../tests-output/project-'.bin2hex(random_bytes(8));

    mkdir($this->tempDir, 0777, true);
});

pest()->afterEach(function (): void {
    deleteDirectory($this->tempDir);
});

function shimBinary(string $tempDir, string $binary): string
{
    $bin = $tempDir.'/bin';
    $log = $tempDir.'/'.$binary.'.log';

    if (! is_dir($bin)) {
        mkdir($bin, 0777, true);
    }

    file_put_contents($bin.'/'.$binary, "#!/bin/sh\nprintf '%s\n' \"$(pwd)|$*\" > \"$log\"\n");
    chmod($bin.'/'.$binary, 0755);

    return $log;
}

function withShimmedPath(string $tempDir, Closure $callback): void
{
    $originalPath = getenv('PATH') ?: '';
    putenv('PATH='.$tempDir.'/bin:'.$originalPath);

    try {
        $callback();
    } finally {
        putenv('PATH='.$originalPath);
    }
}

function deleteDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($directory);
}

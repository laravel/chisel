<?php

pest()->beforeEach(function (): void {
    $this->tempDir = __DIR__.'/../tests-output/project-'.bin2hex(random_bytes(8));

    mkdir($this->tempDir, 0777, true);
});

pest()->afterEach(function (): void {
    deleteDirectory($this->tempDir);
});

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

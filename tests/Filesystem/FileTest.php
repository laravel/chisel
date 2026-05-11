<?php

use Laravel\Chisel\Chisel;

it('replaces matching content in a file', function (): void {
    file_put_contents($this->tempDir.'/composer.json', '"laravel/fortify": "dev-add-passkey-support#242c342"');

    Chisel::in($this->tempDir)->file('composer.json')->replace(
        '"laravel/fortify": "dev-add-passkey-support#242c342"',
        '"laravel/fortify": "^1.30"',
    );

    expect(file_get_contents($this->tempDir.'/composer.json'))->toBe('"laravel/fortify": "^1.30"');
});

it('removes matching single lines from a file', function (): void {
    mkdir($this->tempDir.'/php', 0777, true);

    file_put_contents(
        $this->tempDir.'/php/file.php',
        "Features::registration(),\nFeatures::emailVerification(),\nFeatures::resetPasswords(),\n",
    );

    Chisel::in($this->tempDir)->file('php/file.php')->removeLinesContaining('Features::emailVerification()');

    expect(file_get_contents($this->tempDir.'/php/file.php'))
        ->toBe("Features::registration(),\nFeatures::resetPasswords(),\n");
});

it('removes section markers while keeping content in vue comments', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/file.vue',
        "<!-- @passkeys -->\n<div>Passkey settings</div>\n<!-- @end-passkeys -->\n",
    );

    Chisel::in($this->tempDir)
        ->file('js/file.vue')
        ->removeSectionMarkers('passkeys');

    expect(file_get_contents($this->tempDir.'/js/file.vue'))
        ->toBe("<div>Passkey settings</div>\n");
});

it('removes tagged sections from multiple files', function (): void {
    mkdir($this->tempDir.'/php', 0777, true);
    mkdir($this->tempDir.'/blade', 0777, true);

    file_put_contents($this->tempDir.'/php/file1.php', "before\n/* @2fa */\nremove me\n/* @end-2fa */\nafter\n");
    file_put_contents($this->tempDir.'/php/file2.php', "start\n/* @2fa */\nremove me too\n/* @end-2fa */\nfinish\n");
    file_put_contents($this->tempDir.'/blade/file.blade.php', "hello\n{{-- @2fa --}}\nremove blade section\n{{-- @end-2fa --}}\nworld\n");

    Chisel::in($this->tempDir)->files(
        'php/file1.php',
        'php/file2.php',
        'blade/file.blade.php',
    )->removeSection('2fa');

    expect(file_get_contents($this->tempDir.'/php/file1.php'))->toBe("before\nafter\n")
        ->and(file_get_contents($this->tempDir.'/php/file2.php'))->toBe("start\nfinish\n")
        ->and(file_get_contents($this->tempDir.'/blade/file.blade.php'))->toBe("hello\nworld\n");
});

it('deletes multiple files', function (): void {
    mkdir($this->tempDir.'/tmp', 0777, true);

    file_put_contents($this->tempDir.'/tmp/file1.php', 'x');
    file_put_contents($this->tempDir.'/tmp/file2.php', 'y');

    Chisel::in($this->tempDir)->files(
        'tmp/file1.php',
        'tmp/file2.php',
    )->delete();

    expect($this->tempDir.'/tmp/file1.php')->not->toBeFile()
        ->and($this->tempDir.'/tmp/file2.php')->not->toBeFile();
});

it('ignores missing files when removing section markers from multiple targets', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/file1.tsx',
        "{/* @passkeys */}\n<button>Passkey</button>\n{/* @end-passkeys */}\n",
    );

    Chisel::in($this->tempDir)->files(
        'js/file1.tsx',
        'js/file2.tsx',
    )->removeSectionMarkers('passkeys');

    expect(file_get_contents($this->tempDir.'/js/file1.tsx'))->toBe("<button>Passkey</button>\n")
        ->and($this->tempDir.'/js/file2.tsx')->not->toBeFile();
});

it('ignores missing files when removing tagged sections from multiple targets', function (): void {
    mkdir($this->tempDir.'/php', 0777, true);

    file_put_contents(
        $this->tempDir.'/php/file1.php',
        "before\n/* @2fa */\nremove me\n/* @end-2fa */\nafter\n",
    );

    Chisel::in($this->tempDir)->files(
        'php/file1.php',
        'php/file2.php',
    )->removeSection('2fa');

    expect(file_get_contents($this->tempDir.'/php/file1.php'))->toBe("before\nafter\n")
        ->and($this->tempDir.'/php/file2.php')->not->toBeFile();
});

it('ignores missing files when deleting multiple targets', function (): void {
    mkdir($this->tempDir.'/tmp', 0777, true);

    file_put_contents($this->tempDir.'/tmp/file1.php', 'x');

    Chisel::in($this->tempDir)->files(
        'tmp/file1.php',
        'tmp/file2.php',
    )->delete();

    expect($this->tempDir.'/tmp/file1.php')->not->toBeFile()
        ->and($this->tempDir.'/tmp/file2.php')->not->toBeFile();
});

it('removes react section markers when chisel markers share a line with code', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/file.tsx',
        "/* @chisel-2fa-or-passkeys */ props: Props /* @end-chisel-2fa-or-passkeys */,\n",
    );

    Chisel::in($this->tempDir)
        ->file('js/file.tsx')
        ->removeSectionMarkers('chisel-2fa-or-passkeys');

    expect(file_get_contents($this->tempDir.'/js/file.tsx'))
        ->toBe("props: Props,\n");
});

it('removes adjacent react sections when multiple chisel blocks share a line', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/file.tsx',
        "props: { /* @chisel-2fa */ foo, /* @end-chisel-2fa*/ /* @chisel-passkeys */ bar, /* @end-chisel-passkeys*/ }\n",
    );

    $chisel = Chisel::in($this->tempDir);

    $chisel->file('js/file.tsx')->removeSection('chisel-2fa');
    $chisel->file('js/file.tsx')->removeSectionMarkers('chisel-passkeys');

    expect(file_get_contents($this->tempDir.'/js/file.tsx'))
        ->toBe("props: { bar, }\n");
});

it('does not rewrite unrelated content when section tag is missing', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    $contents = "const x = \"a  b\";\n\nconst y = 1;\n";

    file_put_contents(
        $this->tempDir.'/js/file.tsx',
        $contents,
    );

    Chisel::in($this->tempDir)
        ->file('js/file.tsx')
        ->removeSectionMarkers('missing-tag');

    expect(file_get_contents($this->tempDir.'/js/file.tsx'))
        ->toBe($contents);
});

it('can remove nested sections with different tags', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/file.tsx',
        <<<'TSX'
/* @chisel-2fa-or-passkeys */
type Props = Record<string, never> & {
    /* @chisel-2fa */
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    /* @end-chisel-2fa */
    /* @chisel-passkeys */
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
    /* @end-chisel-passkeys */
};
/* @end-chisel-2fa-or-passkeys */
TSX,
    );

    $chisel = Chisel::in($this->tempDir);
    $file = 'js/file.tsx';

    $chisel->file($file)->removeSection('chisel-2fa');
    $chisel->file($file)->removeSectionMarkers('chisel-passkeys');
    $chisel->file($file)->removeSectionMarkers('chisel-2fa-or-passkeys');

    expect(file_get_contents($this->tempDir.'/'.$file))->toBe(
        "type Props = Record<string, never> & {\n    canManagePasskeys?: boolean;\n    passkeys?: Passkey[];\n};\n",
    );
});

it('throws on consecutive opening markers', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/bad.tsx',
        "/* @chisel-feat */\n/* @chisel-feat */\ncontent\n/* @end-chisel-feat */\n",
    );

    Chisel::in($this->tempDir)
        ->file('js/bad.tsx')
        ->removeSection('chisel-feat');
})->throws(RuntimeException::class, 'Consecutive opening markers for @chisel-feat in js/bad.tsx.');

it('throws on consecutive closing markers', function (): void {
    mkdir($this->tempDir.'/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/js/bad.tsx',
        "/* @chisel-feat */\ncontent\n/* @end-chisel-feat */\n/* @end-chisel-feat */\n",
    );

    Chisel::in($this->tempDir)
        ->file('js/bad.tsx')
        ->removeSectionMarkers('chisel-feat');
})->throws(RuntimeException::class, 'Consecutive closing markers for @chisel-feat in js/bad.tsx.');

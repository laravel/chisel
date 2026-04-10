<?php

use Laravel\Chisel\Chisel;

it('applies subtractive file mutations', function (): void {
    mkdir($this->tempDir.'/config', 0777, true);
    mkdir($this->tempDir.'/resources/js/pages/auth', 0777, true);
    mkdir($this->tempDir.'/resources/js/pages/settings', 0777, true);
    mkdir($this->tempDir.'/routes', 0777, true);
    mkdir($this->tempDir.'/resources/views/settings', 0777, true);
    mkdir($this->tempDir.'/tests/Feature/Auth', 0777, true);

    file_put_contents($this->tempDir.'/composer.json', '"laravel/fortify": "dev-add-passkey-support#242c342"');
    file_put_contents($this->tempDir.'/config/fortify.php', "Features::registration(),\nFeatures::emailVerification(),\nFeatures::resetPasswords(),\n");
    file_put_contents($this->tempDir.'/resources/js/pages/auth/login.tsx', "{/* @passkeys */}\n<button>Passkey</button>\n{/* @end-passkeys */}\n");
    file_put_contents($this->tempDir.'/resources/js/pages/settings/Security.vue', "<!-- @passkeys -->\n<div>Passkey settings</div>\n<!-- @end-passkeys -->\n");
    file_put_contents($this->tempDir.'/routes/settings.php', "before\n/* @2fa */\nremove me\n/* @end-2fa */\nafter\n");
    file_put_contents($this->tempDir.'/routes/profile.php', "start\n/* @2fa */\nremove me too\n/* @end-2fa */\nfinish\n");
    file_put_contents($this->tempDir.'/resources/views/settings/security.blade.php', "hello\n{{-- @2fa --}}\nremove blade section\n{{-- @end-2fa --}}\nworld\n");
    file_put_contents($this->tempDir.'/tests/Feature/Auth/PasskeyTest.php', 'x');
    file_put_contents($this->tempDir.'/tests/Feature/Auth/TwoFactorTest.php', 'y');

    $chisel = Chisel::in($this->tempDir);

    $chisel->file('composer.json')->replace(
        '"laravel/fortify": "dev-add-passkey-support#242c342"',
        '"laravel/fortify": "^1.30"',
    );
    $chisel->file('config/fortify.php')->removeLinesContaining('Features::emailVerification()');
    $chisel->file('resources/js/pages/auth/login.tsx')->removeSectionMarkers('passkeys');
    $chisel->file('resources/js/pages/settings/Security.vue')->removeSectionMarkers('passkeys');
    $chisel->files(
        'routes/settings.php',
        'routes/profile.php',
        'resources/views/settings/security.blade.php',
    )->removeSection('2fa');
    $chisel->files(
        'tests/Feature/Auth/PasskeyTest.php',
        'tests/Feature/Auth/TwoFactorTest.php',
    )->delete();

    expect(file_get_contents($this->tempDir.'/composer.json'))->toBe('"laravel/fortify": "^1.30"')
        ->and(file_get_contents($this->tempDir.'/config/fortify.php'))->not->toContain('Features::emailVerification()')
        ->and(file_get_contents($this->tempDir.'/resources/js/pages/auth/login.tsx'))->toBe("<button>Passkey</button>\n")
        ->and(file_get_contents($this->tempDir.'/resources/js/pages/settings/Security.vue'))->toBe("<div>Passkey settings</div>\n")
        ->and(file_get_contents($this->tempDir.'/routes/settings.php'))->toBe("before\nafter\n")
        ->and(file_get_contents($this->tempDir.'/routes/profile.php'))->toBe("start\nfinish\n")
        ->and(file_get_contents($this->tempDir.'/resources/views/settings/security.blade.php'))->toBe("hello\nworld\n")
        ->and($this->tempDir.'/tests/Feature/Auth/PasskeyTest.php')->not->toBeFile()
        ->and($this->tempDir.'/tests/Feature/Auth/TwoFactorTest.php')->not->toBeFile();
});

it('ignores missing files during subtractive mutations', function (): void {
    mkdir($this->tempDir.'/resources/js/pages/auth', 0777, true);
    mkdir($this->tempDir.'/routes', 0777, true);

    file_put_contents($this->tempDir.'/resources/js/pages/auth/login.tsx', "{/* @passkeys */}\n<button>Passkey</button>\n{/* @end-passkeys */}\n");
    file_put_contents($this->tempDir.'/routes/settings.php', "before\n/* @2fa */\nremove me\n/* @end-2fa */\nafter\n");

    $chisel = Chisel::in($this->tempDir);

    $chisel->files(
        'resources/js/pages/auth/login.tsx',
        'resources/js/pages/auth/confirm-password.tsx',
    )->removeSectionMarkers('passkeys');

    $chisel->files(
        'routes/settings.php',
        'routes/profile.php',
    )->removeSection('2fa');

    $chisel->files(
        'tests/Feature/Auth/PasskeyTest.php',
        'tests/Feature/Auth/TwoFactorTest.php',
    )->delete();

    expect(file_get_contents($this->tempDir.'/resources/js/pages/auth/login.tsx'))->toBe("<button>Passkey</button>\n")
        ->and(file_get_contents($this->tempDir.'/routes/settings.php'))->toBe("before\nafter\n")
        ->and($this->tempDir.'/resources/js/pages/auth/confirm-password.tsx')->not->toBeFile()
        ->and($this->tempDir.'/routes/profile.php')->not->toBeFile()
        ->and($this->tempDir.'/tests/Feature/Auth/PasskeyTest.php')->not->toBeFile()
        ->and($this->tempDir.'/tests/Feature/Auth/TwoFactorTest.php')->not->toBeFile();
});

it('removes react section markers when chisel markers share a line with code', function (): void {
    mkdir($this->tempDir.'/resources/js/components', 0777, true);

    file_put_contents(
        $this->tempDir.'/resources/js/components/InlineSharedProps.tsx',
        "/* @chisel-2fa-or-passkeys */ props: Props /* @end-chisel-2fa-or-passkeys */,\n",
    );

    Chisel::in($this->tempDir)
        ->file('resources/js/components/InlineSharedProps.tsx')
        ->removeSectionMarkers('chisel-2fa-or-passkeys');

    expect(file_get_contents($this->tempDir.'/resources/js/components/InlineSharedProps.tsx'))
        ->toBe("props: Props,\n");
});

it('removes adjacent react sections when multiple chisel blocks share a line', function (): void {
    mkdir($this->tempDir.'/resources/js/components', 0777, true);

    file_put_contents(
        $this->tempDir.'/resources/js/components/InlineFeatureProps.tsx',
        "props: { /* @chisel-2fa */ foo, /* @end-chisel-2fa*/ /* @chisel-passkeys */ bar, /* @end-chisel-passkeys*/ }\n",
    );

    $chisel = Chisel::in($this->tempDir);

    $chisel->file('resources/js/components/InlineFeatureProps.tsx')->removeSection('chisel-2fa');
    $chisel->file('resources/js/components/InlineFeatureProps.tsx')->removeSectionMarkers('chisel-passkeys');

    expect(file_get_contents($this->tempDir.'/resources/js/components/InlineFeatureProps.tsx'))
        ->toBe("props: { bar, }\n");
});

it('does not rewrite unrelated content when section tag is missing', function (): void {
    mkdir($this->tempDir.'/resources/js/components', 0777, true);

    $contents = "const x = \"a  b\";\n\nconst y = 1;\n";

    file_put_contents(
        $this->tempDir.'/resources/js/components/NoTag.tsx',
        $contents,
    );

    Chisel::in($this->tempDir)
        ->file('resources/js/components/NoTag.tsx')
        ->removeSectionMarkers('missing-tag');

    expect(file_get_contents($this->tempDir.'/resources/js/components/NoTag.tsx'))
        ->toBe($contents);
});

it('can remove nested sections with different tags', function (): void {
    mkdir($this->tempDir.'/resources/js/components', 0777, true);

    file_put_contents(
        $this->tempDir.'/resources/js/components/Security.tsx',
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
    $file = 'resources/js/components/Security.tsx';

    $chisel->file($file)->removeSection('chisel-2fa');
    $chisel->file($file)->removeSectionMarkers('chisel-passkeys');
    $chisel->file($file)->removeSectionMarkers('chisel-2fa-or-passkeys');

    expect(file_get_contents($this->tempDir.'/'.$file))->toBe(
        "type Props = Record<string, never> & {\n    canManagePasskeys?: boolean;\n    passkeys?: Passkey[];\n};\n",
    );
});

it('throws on consecutive opening markers', function (): void {
    mkdir($this->tempDir.'/resources/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/resources/js/bad.tsx',
        "/* @chisel-feat */\n/* @chisel-feat */\ncontent\n/* @end-chisel-feat */\n",
    );

    Chisel::in($this->tempDir)
        ->file('resources/js/bad.tsx')
        ->removeSection('chisel-feat');
})->throws(RuntimeException::class, 'Consecutive opening markers for @chisel-feat in resources/js/bad.tsx.');

it('throws on consecutive closing markers', function (): void {
    mkdir($this->tempDir.'/resources/js', 0777, true);

    file_put_contents(
        $this->tempDir.'/resources/js/bad.tsx',
        "/* @chisel-feat */\ncontent\n/* @end-chisel-feat */\n/* @end-chisel-feat */\n",
    );

    Chisel::in($this->tempDir)
        ->file('resources/js/bad.tsx')
        ->removeSectionMarkers('chisel-feat');
})->throws(RuntimeException::class, 'Consecutive closing markers for @chisel-feat in resources/js/bad.tsx.');

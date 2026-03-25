# Laravel Chisel

Laravel Chisel is a toolkit for building scripts remove unwanted code, files, and dependencies. It's used by the official Fortify starter kits to allow users to customize which authentication features they want in their application.

## Install

```bash
composer require --dev laravel/chisel:dev-main
```

## Running A Chisel Script

Create a `chisel.php` file in the project root, then run:

```bash
php artisan chisel
```

Use `--path` to run a different script:

```bash
php artisan chisel --path=example-chisel.php
```

Use `--answers` to pass preselected answers as JSON:

```bash
php artisan chisel --path=example-chisel.php --answers='{"auth_features":["email-verification"]}'
```

Use `--delete-script` to delete the script after a successful run:

```bash
php artisan chisel --delete-script
```

The command runs the chisel script, then runs `npm install` and `npm run build`.

## Example

```php
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;

$c = Chisel::in(dirname(__DIR__))
    ->withAnswers($argv[1] ?? null)
    ->multiselect('auth_features', 'Which authentication features would you like to enable?', [
        'email-verification' => 'Email verification',
        '2fa' => 'Two-factor authentication',
        'passkeys' => 'Passkeys',
    ], hint: 'Use space to select, enter to confirm.');

$c->selected('auth_features', 'email-verification',
    then: function (Chisel $c) {
        $c->files(
            'resources/js/pages/settings/profile.tsx',
            'app/Providers/FortifyServiceProvider.php',
        )->removeSectionMarkers('email-verification');
    },
    else: function (Chisel $c) {
        $c->phpFile('app/Models/User.php')
            ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
            ->removeInterface('MustVerifyEmail');

        $c->file('config/fortify.php')->removeLinesContaining('Features::emailVerification()');

        $c->files(
            'app/Providers/FortifyServiceProvider.php',
            'resources/js/pages/settings/profile.tsx',
        )->removeSection('email-verification');

        $c->files(
            'resources/js/components/email-verification-notice.tsx',
            'resources/js/pages/auth/verify-email.tsx',
            'tests/Feature/Auth/EmailVerificationTest.php',
            'tests/Feature/Auth/VerificationNotificationTest.php',
        )->delete();
    },
);
```

`withAnswers()` accepts a JSON string. Pass `null` to prompt interactively, or pass a JSON payload in `$argv[1]` to skip prompts.

## API

### Prompts And Branching

| Method | Purpose |
|---|---|
| `withAnswers(?string $json)` | Hydrate answers from JSON |
| `multiselect($name, $label, $options, $default = [], $hint = '', $required = false)` | Ask for feature selections |
| `selected($key, $value, then:, else:)` | Branch on a multiselect answer |
| `selectedAny($key, $values, then:, else:)` | Branch when any of several multiselect answers are selected |

### File Mutations

`file($path)` targets one file. `files(...$paths)` targets many files.

| Method | Purpose |
|---|---|
| `replace($search, $replace)` | Replace a string |
| `removeLinesContaining($content)` | Remove lines containing a string |
| `removeSectionMarkers($tag)` | Remove section markers and keep the content |
| `removeSection($tag)` | Remove the section markers and the content inside them |
| `delete()` | Delete the targeted files |

### PHP File Mutations

`phpFile($path)` applies AST-based edits and saves automatically when the object is destroyed.

| Method | Purpose |
|---|---|
| `removeImport($class)` | Remove a `use` statement |
| `removeTrait($trait)` | Remove a trait from the class |
| `removeInterface($interface)` | Remove an implemented interface |

### npm

| Method | Purpose |
|---|---|
| `npm()->remove(...$packages)` | Remove npm packages |

## Section Markers

Use comment pairs to wrap optional code:

```php
/* @passkeys */
Fortify::authenticateUsingPasskeys();
/* @end-passkeys */
```

JS and JSX files can use block comments with braces:

```tsx
{/* @passkeys */}
<PasskeyButton />
{/* @end-passkeys */}
```

`removeSectionMarkers('passkeys')` keeps the code and removes the markers.
`removeSection('passkeys')` removes both the markers and the code inside them.

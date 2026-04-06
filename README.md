<p align="center">
<a href="https://github.com/laravel/chisel/actions"><img src="https://github.com/laravel/chisel/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/chisel"><img src="https://img.shields.io/packagist/dt/laravel/chisel" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/chisel"><img src="https://img.shields.io/packagist/v/laravel/chisel" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/chisel"><img src="https://img.shields.io/packagist/l/laravel/chisel" alt="License"></a>
</p>

## Introduction

Laravel Chisel provides primitives for building post-install scripts that remove unwanted features from Laravel starter kits. Compatible starter kits include a `chisel.php` script that defines the optional features and the file mutations needed to remove them.

## Installation

```bash
composer require laravel/chisel
```

## Usage

An example `chisel.php` script might look like this:

```php
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;

return Chisel::script(dirname(__DIR__))
    ->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
            hint: 'Use space to select, enter to confirm.',
        ),
    ])
    ->selected('auth_features', 'email-verification',
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

Although the questions are defined in the `chisel.php` file, an external process such as an Artisan command is responsible for rendering them with [Laravel Prompts](https://laravel.com/docs/prompts) and passing the answers to Chisel's `run()` method. An example Artisan command might look like this:

```php
use Illuminate\Console\Command;
use function Laravel\Prompts\multiselect;

class InstallFeatures extends Command
{
    protected $signature = 'install:features';

    public function handle(): void
    {
        $script = require base_path('chisel.php');
        $question = $script->questions()[0];

        $answers = [
            $question->name => multiselect(
                label: $question->label,
                options: $question->options,
                default: $question->default ?? [],
                required: $question->required,
                hint: $question->hint,
            ),
        ];

        $script->run($answers);
    }
}
```

## Script Definitions

| Method | Purpose |
|---|---|
| `Chisel::script($directory)` | Create a script definition |
| `Question::multiselect(...)` | Define a multiselect question |
| `questions([...])` | Set the script's questions |
| `questions()` | Retrieve the registered questions |
| `apply($callback)` | Register an unconditional mutation step |
| `selected($key, $value, then:, else:)` | Branch on a multiselect answer |
| `selectedAny($key, $values, then:, else:)` | Branch when any of the given values are selected |
| `run($answers)` | Execute the registered mutations |

## File Mutations

`file($path)` targets a single file. `files(...$paths)` targets multiple files.

| Method | Purpose |
|---|---|
| `replace($search, $replace)` | Replace a string |
| `removeLinesContaining($content)` | Remove lines containing a string |
| `removeSectionMarkers($tag)` | Strip section markers, keep the content |
| `removeSection($tag)` | Remove section markers and the content inside them |
| `delete()` | Delete the targeted files |

## PHP File Mutations

`phpFile($path)` provides AST-based edits. Changes are saved automatically when the object is destroyed.

| Method | Purpose |
|---|---|
| `removeImport($class)` | Remove a `use` statement |
| `removeTrait($trait)` | Remove a trait usage from the class |
| `removeInterface($interface)` | Remove an implemented interface |

## npm

| Method | Purpose |
|---|---|
| `npm()->remove(...$packages)` | Remove packages using the detected package manager |

The `npm()` method detects `npm`, `yarn`, `pnpm`, and `bun` automatically.

## Section Markers

Wrap optional code in comment pairs:

```php
/* @passkeys */
Fortify::authenticateUsingPasskeys();
/* @end-passkeys */
```

JSX files may use block comments with braces:

```tsx
{/* @passkeys */}
<PasskeyButton />
{/* @end-passkeys */}
```

`removeSectionMarkers('passkeys')` keeps the code and removes the markers. `removeSection('passkeys')` removes both.

## Contributing

Thank you for considering contributing to Chisel! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/chisel/security/policy) on how to report security vulnerabilities.

## License

Laravel Chisel is open-sourced software licensed under the [MIT license](LICENSE.md).

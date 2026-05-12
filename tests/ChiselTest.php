<?php

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;

it('registers questions separately from mutations', function (): void {
    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
            default: ['passkeys'],
            hint: 'Use space to select, enter to confirm.',
        ),
    ]);

    expect($script->questions())->toHaveCount(1)
        ->and($script->questions()[0]->type)->toBe('multiselect')
        ->and($script->questions()[0]->name)->toBe('auth_features')
        ->and($script->questions()[0]->default)->toBe(['passkeys']);
});

it('runs unconditional mutations during run', function (): void {
    $ran = false;

    Chisel::script($this->tempDir)
        ->apply(function () use (&$ran): void {
            $ran = true;
        })
        ->run([]);

    expect($ran)->toBeTrue();
});

it('branches on selected multiselect answers during run', function (): void {
    $branches = [];

    Chisel::script($this->tempDir)
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
        ->selected('auth_features', 'email-verification', then: function (Chisel $chisel) use (&$branches): void {
            $branches[] = $chisel::class;
        })
        ->selected('auth_features', 'passkeys', else: function (Chisel $chisel) use (&$branches): void {
            $branches[] = $chisel::class;
        })
        ->run(['auth_features' => ['email-verification']]);

    expect($branches)->toBe([Chisel::class, Chisel::class]);
});

it('branches when any multiselect answer is selected during run', function (): void {
    $branches = [];

    Chisel::script($this->tempDir)
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
        ->selectedAny('auth_features', ['2fa', 'passkeys'], then: function (Chisel $chisel) use (&$branches): void {
            $branches[] = $chisel::class;
        })
        ->selectedAny('auth_features', ['email-verification', '2fa'], else: function (Chisel $chisel) use (&$branches): void {
            $branches[] = $chisel::class;
        })
        ->run(['auth_features' => ['passkeys']]);

    expect($branches)->toBe([Chisel::class, Chisel::class]);
});

it('branches when all multiselect answers are selected during run', function (): void {
    $branches = [];

    Chisel::script($this->tempDir)
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
        ->selectedAll('auth_features', ['2fa', 'passkeys'], then: function (Chisel $chisel) use (&$branches): void {
            $branches[] = $chisel::class;
        })
        ->selectedAll('auth_features', ['email-verification', '2fa'], else: function (Chisel $chisel) use (&$branches): void {
            $branches[] = $chisel::class;
        })
        ->run(['auth_features' => ['2fa', 'passkeys']]);

    expect($branches)->toBe([Chisel::class, Chisel::class]);
});

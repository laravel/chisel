<?php

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;

it('runs commands in the project directory', function (): void {
    $log = shimBinary($this->tempDir, 'php');

    withShimmedPath($this->tempDir, fn () => Chisel::in($this->tempDir)->run(['php', 'artisan', 'about']));

    expect(file_get_contents($log))
        ->toContain(realpath($this->tempDir))
        ->toContain('artisan about');
});

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

it('runs unconditional mutations', function (): void {
    $ran = false;

    Chisel::script($this->tempDir)
        ->apply(function () use (&$ran): void {
            $ran = true;
        })
        ->chisel([]);

    expect($ran)->toBeTrue();
});

it('collects answers with an ask callback', function (): void {
    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
        ),
    ]);

    $answers = $script
        ->collectAnswers()
        ->onQuestion(fn (Question $question): array => ['2fa']);

    expect($answers->toArray())->toBe(['auth_features' => ['2fa']]);
});

it('keeps provided answers when collecting answers', function (): void {
    $asked = false;

    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
        ),
    ]);

    $answers = $script
        ->collectAnswers()
        ->onQuestion(function () use (&$asked): array {
            $asked = true;

            return ['2fa'];
        })
        ->withAnswers(['auth_features' => ['passkeys']]);

    expect($answers->toArray())->toBe(['auth_features' => ['passkeys']])
        ->and($asked)->toBeFalse();
});

it('uses defaults automatically when non-interactive', function (): void {
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
        ),
    ]);

    $answers = $script
        ->collectAnswers()
        ->onQuestion(fn (): array => ['2fa'])
        ->interactive(false);

    expect($answers->toArray())->toBe(['auth_features' => ['passkeys']]);
});

it('throws when a required question has no answer non-interactively', function (): void {
    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
            required: true,
        ),
    ]);

    $script
        ->collectAnswers()
        ->onQuestion(fn (): array => ['2fa'])
        ->interactive(false)
        ->toArray();
})->throws(RuntimeException::class, 'Question [auth_features] requires an answer.');

it('uses an empty array for optional unanswered questions non-interactively', function (): void {
    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
        ),
    ]);

    $answers = $script
        ->collectAnswers()
        ->onQuestion(fn (): array => ['2fa'])
        ->interactive(false);

    expect($answers->toArray())->toBe(['auth_features' => []]);
});

it('works as an array via ArrayAccess without calling toArray', function (): void {
    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
        ),
    ]);

    $answers = $script
        ->collectAnswers()
        ->onQuestion(fn (Question $question): array => ['2fa']);

    expect($answers['auth_features'])->toBe(['2fa'])
        ->and(isset($answers['auth_features']))->toBeTrue();
});

it('is iterable without calling toArray', function (): void {
    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
        ),
    ]);

    $answers = $script
        ->collectAnswers()
        ->onQuestion(fn (Question $question): array => ['2fa']);

    $collected = [];
    foreach ($answers as $key => $value) {
        $collected[$key] = $value;
    }

    expect($collected)->toBe(['auth_features' => ['2fa']]);
});

it('can be passed directly to chisel', function (): void {
    $ran = false;
    $receivedAnswers = [];

    $script = Chisel::script($this->tempDir)->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
            ],
        ),
    ])->apply(function ($chisel, $answers) use (&$ran, &$receivedAnswers): void {
        $ran = true;
        $receivedAnswers = $answers;
    });

    $script->chisel(
        $script->collectAnswers()->onQuestion(fn (): array => ['2fa'])
    );

    expect($ran)->toBeTrue()
        ->and($receivedAnswers)->toBe(['auth_features' => ['2fa']]);
});

it('branches on selected multiselect answers', function (): void {
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
        ->chisel(['auth_features' => ['email-verification']]);

    expect($branches)->toBe([Chisel::class, Chisel::class]);
});

it('branches when any multiselect answer is selected', function (): void {
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
        ->chisel(['auth_features' => ['passkeys']]);

    expect($branches)->toBe([Chisel::class, Chisel::class]);
});

it('branches when all multiselect answers are selected', function (): void {
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
        ->chisel(['auth_features' => ['2fa', 'passkeys']]);

    expect($branches)->toBe([Chisel::class, Chisel::class]);
});

<?php

namespace Laravel\Chisel;

use Closure;

class Script
{
    /** @var array<int, Question> */
    private array $questions = [];

    /** @var array<int, Closure(Chisel, array<string, mixed>): void> */
    private array $mutations = [];

    public function __construct(private readonly string $directory)
    {
        //
    }

    /**
     * @param  array<int, Question>|null  $questions
     * @return ($questions is null ? array<int, Question> : static)
     */
    public function questions(?array $questions = null): array|static
    {
        if ($questions === null) {
            return $this->questions;
        }

        $this->questions = $questions;

        return $this;
    }

    /**
     * @param  array<string, mixed>|PendingAnswers  $answers
     */
    public function chisel(array|PendingAnswers $answers): void
    {
        $chisel = Chisel::in($this->directory);

        $answers = $answers instanceof PendingAnswers ? $answers->toArray() : $answers;

        foreach ($this->mutations as $mutation) {
            $mutation($chisel, $answers);
        }
    }

    /**
     * @param  callable(Chisel, array<string, mixed>): void  $callback
     */
    public function apply(callable $callback): static
    {
        $this->mutations[] = function (Chisel $chisel, array $answers) use ($callback): void {
            $callback($chisel, $answers);
        };

        return $this;
    }

    public function collectAnswers(): PendingAnswers
    {
        return new PendingAnswers($this->questions);
    }

    /**
     * @param  callable(Chisel): void|null  $then
     * @param  callable(Chisel): void|null  $else
     */
    public function selected(string $key, string $value, ?callable $then = null, ?callable $else = null): static
    {
        return $this->selectedAny($key, [$value], $then, $else);
    }

    /**
     * @param  array<int, string>  $values
     * @param  callable(Chisel): void|null  $then
     * @param  callable(Chisel): void|null  $else
     */
    public function selectedAll(string $key, array $values, ?callable $then = null, ?callable $else = null): static
    {
        $this->mutations[] = function (Chisel $chisel, array $answers) use ($key, $values, $then, $else): void {
            $selected = (array) ($answers[$key] ?? []);

            foreach ($values as $value) {
                if (in_array($value, $selected)) {
                    continue;
                }

                if ($else !== null) {
                    $else($chisel);
                }

                return;
            }

            if ($then !== null) {
                $then($chisel);
            }
        };

        return $this;
    }

    /**
     * @param  array<int, string>  $values
     * @param  callable(Chisel): void|null  $then
     * @param  callable(Chisel): void|null  $else
     */
    public function selectedAny(string $key, array $values, ?callable $then = null, ?callable $else = null): static
    {
        $this->mutations[] = function (Chisel $chisel, array $answers) use ($key, $values, $then, $else): void {
            $selected = (array) ($answers[$key] ?? []);

            foreach ($values as $value) {
                if (! in_array($value, $selected)) {
                    continue;
                }

                if ($then !== null) {
                    $then($chisel);
                }

                return;
            }

            if ($else !== null) {
                $else($chisel);
            }
        };

        return $this;
    }
}

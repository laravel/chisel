<?php

namespace Laravel\Chisel;

use Closure;
use RuntimeException;

class PendingAnswers
{
    protected bool $interactive = true;

    protected bool $withDefaults = false;

    /**
     * @param  array<int, Question>  $questions
     * @param  Closure(Question): mixed  $ask
     */
    public function __construct(
        protected array $questions,
        protected Closure $ask,
    ) {
        //
    }

    public function interactive(bool $interactive = true): static
    {
        $this->interactive = $interactive;

        return $this;
    }

    public function withDefaults(bool $withDefaults = true): static
    {
        $this->withDefaults = $withDefaults;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function withAnswers(array $answers = []): array
    {
        foreach ($this->questions as $question) {
            if (array_key_exists($question->name, $answers)) {
                continue;
            }

            if (! $this->interactive) {
                $answers[$question->name] = $this->defaultAnswer($question);

                continue;
            }

            $answers[$question->name] = ($this->ask)($question);
        }

        return $answers;
    }

    protected function defaultAnswer(Question $question): mixed
    {
        if ($this->withDefaults && $question->default !== null) {
            return $question->default;
        }

        if ($question->required) {
            throw new RuntimeException("Question [{$question->name}] requires an answer.");
        }

        return [];
    }
}

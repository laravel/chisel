<?php

namespace Laravel\Chisel;

use ArrayAccess;
use ArrayIterator;
use Closure;
use IteratorAggregate;
use RuntimeException;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class PendingAnswers implements ArrayAccess, IteratorAggregate
{
    protected ?Closure $onQuestion = null;

    protected bool $interactive = true;

    /** @var array<string, mixed> */
    protected array $providedAnswers = [];

    /** @var array<string, mixed>|null */
    protected ?array $resolved = null;

    /**
     * @param  array<int, Question>  $questions
     */
    public function __construct(protected array $questions)
    {
        //
    }

    public function onQuestion(callable $callback): static
    {
        $this->onQuestion = $callback(...);

        return $this;
    }

    /**
     * Indicate whether the answers should be resolved
     * interactively (prompting the user for input) or
     * non-interactively (using default values or provided answers).
     */
    public function interactive(bool $interactive = true): static
    {
        $this->interactive = $interactive;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public function withAnswers(array $answers = []): static
    {
        $this->providedAnswers = $answers;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $answers = $this->providedAnswers;

        foreach ($this->questions as $question) {
            if (array_key_exists($question->name, $answers)) {
                continue;
            }

            if (! $this->interactive) {
                $answers[$question->name] = $this->defaultAnswer($question);

                continue;
            }

            if ($this->onQuestion === null) {
                throw new RuntimeException('No question handler registered. Call onQuestion() before resolving answers.');
            }

            $answers[$question->name] = ($this->onQuestion)($question);
        }

        return $this->resolved = $answers;
    }

    protected function defaultAnswer(Question $question): mixed
    {
        if ($question->default !== null) {
            return $question->default;
        }

        if ($question->required) {
            throw new RuntimeException("Question [{$question->name}] requires an answer.");
        }

        return [];
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('PendingAnswers is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('PendingAnswers is read-only.');
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }
}

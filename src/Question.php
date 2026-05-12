<?php

namespace Laravel\Chisel;

readonly class Question
{
    /**
     * @param  'multiselect'  $type
     * @param  array<int|string, string>  $options
     * @param  array<int, int|string>|null  $default
     */
    private function __construct(
        public string $type,
        public string $name,
        public string $label,
        public array $options = [],
        public ?array $default = null,
        public bool|string $required = false,
        public string $placeholder = '',
        public string $hint = '',
    ) {
        //
    }

    /**
     * @param  array<int|string, string>  $options
     * @param  array<int, int|string>|null  $default
     */
    public static function multiselect(
        string $name,
        string $label,
        array $options,
        ?array $default = null,
        string $hint = '',
        bool|string $required = false,
    ): self {
        return new self(
            type: 'multiselect',
            name: $name,
            label: $label,
            options: $options,
            default: $default,
            required: $required,
            hint: $hint,
        );
    }
}

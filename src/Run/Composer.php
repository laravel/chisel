<?php

namespace Laravel\Chisel\Run;

use Laravel\Chisel\Chisel;

class Composer
{
    public function __construct(protected Chisel $chisel)
    {
        //
    }

    public function remove(string ...$packages): void
    {
        $this->chisel->run(['composer', 'remove', ...$packages, '--no-interaction']);
    }
}

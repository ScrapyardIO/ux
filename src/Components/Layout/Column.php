<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Enums\Axis;

/**
 * Children stacked top to bottom.
 */
class Column extends Flex
{
    public function __construct(int $gap = 0)
    {
        parent::__construct(Axis::VERTICAL, $gap);
    }

    public static function of(int $gap = 0): static
    {
        return new static($gap);
    }
}

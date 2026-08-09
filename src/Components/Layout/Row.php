<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Enums\Axis;

/**
 * Children side by side, left to right.
 */
class Row extends Flex
{
    public function __construct(int $gap = 0)
    {
        parent::__construct(Axis::HORIZONTAL, $gap);
    }

    public static function of(int $gap = 0): static
    {
        return new static($gap);
    }
}

<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;

/**
 * Explicit empty box with a fixed width and/or height.
 */
class SizedBox extends UIComponent
{
    public function __construct(?int $width = null, ?int $height = null)
    {
        parent::__construct();

        $this->setSize(max(0, $width ?? 0), max(0, $height ?? 0));
    }

    public static function of(?int $width = null, ?int $height = null): static
    {
        return new static($width, $height);
    }

    public static function square(int $extent): static
    {
        return new static($extent, $extent);
    }

    public static function width(int $width): static
    {
        return new static($width, 0);
    }

    public static function height(int $height): static
    {
        return new static(0, $height);
    }

    protected function draw(PaintContext $ctx): void
    {
        // Empty.
    }
}

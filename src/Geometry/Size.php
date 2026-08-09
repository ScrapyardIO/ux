<?php

namespace ScrapyardIO\UX\Geometry;

readonly class Size
{
    public function __construct(
        public int $width = 0,
        public int $height = 0,
    ) {}

    public static function zero(): self
    {
        return new self(0, 0);
    }

    public function isEmpty(): bool
    {
        return $this->width <= 0 || $this->height <= 0;
    }

    public function equals(self $other): bool
    {
        return $this->width === $other->width && $this->height === $other->height;
    }

    public function at(Point $origin): Rect
    {
        return new Rect($origin->x, $origin->y, $this->width, $this->height);
    }

    public function atOrigin(): Rect
    {
        return new Rect(0, 0, $this->width, $this->height);
    }
}

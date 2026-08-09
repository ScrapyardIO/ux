<?php

namespace ScrapyardIO\UX\Geometry;

readonly class Point
{
    public function __construct(
        public int $x = 0,
        public int $y = 0,
    ) {}

    public static function origin(): self
    {
        return new self(0, 0);
    }

    public function translated(int $dx, int $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy);
    }

    public function equals(self $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }
}

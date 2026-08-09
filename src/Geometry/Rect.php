<?php

namespace ScrapyardIO\UX\Geometry;

readonly class Rect
{
    public function __construct(
        public int $x = 0,
        public int $y = 0,
        public int $width = 0,
        public int $height = 0,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0);
    }

    public static function fromPoints(Point $origin, Size $size): self
    {
        return new self($origin->x, $origin->y, $size->width, $size->height);
    }

    public function isEmpty(): bool
    {
        return $this->width <= 0 || $this->height <= 0;
    }

    public function origin(): Point
    {
        return new Point($this->x, $this->y);
    }

    public function size(): Size
    {
        return new Size($this->width, $this->height);
    }

    public function right(): int
    {
        return $this->x + $this->width;
    }

    public function bottom(): int
    {
        return $this->y + $this->height;
    }

    public function contains(int $px, int $py): bool
    {
        return $px >= $this->x
            && $py >= $this->y
            && $px < $this->right()
            && $py < $this->bottom();
    }

    public function translated(int $dx, int $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy, $this->width, $this->height);
    }

    public function intersect(self $other): self
    {
        $x1 = max($this->x, $other->x);
        $y1 = max($this->y, $other->y);
        $x2 = min($this->right(), $other->right());
        $y2 = min($this->bottom(), $other->bottom());

        if ($x2 <= $x1 || $y2 <= $y1) {
            return self::empty();
        }

        return new self($x1, $y1, $x2 - $x1, $y2 - $y1);
    }

    public function equals(self $other): bool
    {
        return $this->x === $other->x
            && $this->y === $other->y
            && $this->width === $other->width
            && $this->height === $other->height;
    }
}

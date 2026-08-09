<?php

namespace ScrapyardIO\UX\Components\Indicators;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\Axis;
use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * On-screen mirror of a physical LED strip: a row/column of lit cells.
 */
class PixelStrip extends UIComponent
{
    /**
     * @var array<int, Color>
     */
    protected array $pixels = [];

    protected Axis $axis;

    protected int $cell;

    protected int $gap;

    protected bool $round = true;

    protected ?Color $socket;

    public function __construct(int $count = 1, Axis $axis = Axis::HORIZONTAL)
    {
        parent::__construct();

        $this->axis = $axis;
        $this->cell = Theme::metric('pixel_radius', 3) * 2;
        $this->gap = Theme::metric('gap', 2);
        $this->socket = Theme::color('track');
        $this->setCount($count);
    }

    public static function of(int $count, Axis $axis = Axis::HORIZONTAL): static
    {
        return new static($count, $axis);
    }

    public function count(): int
    {
        return count($this->pixels);
    }

    public function setCount(int $count): static
    {
        $count = max(0, $count);
        $dark = Color::transparent();
        $pixels = [];

        for ($index = 0; $index < $count; $index++) {
            $pixels[] = $this->pixels[$index] ?? $dark;
        }

        $this->pixels = $pixels;
        $this->applyIntrinsicSize();

        return $this;
    }

    public function pixel(int $index): Color
    {
        return $this->pixels[$index] ?? Color::transparent();
    }

    public function setPixel(int $index, Color $color): static
    {
        if (! array_key_exists($index, $this->pixels)) {
            return $this;
        }

        $this->pixels[$index] = $color;

        return $this;
    }

    /**
     * @param  array<int, Color>  $colors
     */
    public function setPixels(array $colors): static
    {
        foreach (array_values($colors) as $index => $color) {
            if (! array_key_exists($index, $this->pixels)) {
                continue;
            }

            $this->pixels[$index] = $color;
        }

        return $this;
    }

    public function clear(): static
    {
        return $this->setPixels(array_fill(0, count($this->pixels), Color::transparent()));
    }

    public function setCell(int $cell, ?int $gap = null): static
    {
        $this->cell = max(1, $cell);
        $this->gap = max(0, $gap ?? $this->gap);
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setRound(bool $round = true): static
    {
        $this->round = $round;

        return $this;
    }

    public function setSocket(?Color $socket): static
    {
        $this->socket = $socket;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $count = count($this->pixels);

        if ($count === 0) {
            return;
        }

        $radius = intdiv($this->cell, 2);
        $socketed = ! is_null($this->socket) && ! $this->socket->isTransparent();
        $core = $socketed ? max(1, $radius - max(1, intdiv($radius, 3))) : $radius;
        $origin = $this->worldOrigin();

        foreach ($this->pixels as $index => $color) {
            $offset = $index * ($this->cell + $this->gap);
            $x = ($this->axis === Axis::HORIZONTAL) ? $offset : 0;
            $y = ($this->axis === Axis::HORIZONTAL) ? 0 : $offset;

            if ($socketed) {
                $this->paintCell($ctx, $origin, $x, $y, $radius, $radius, $this->socket->pack());
            }

            if ($color->isTransparent()) {
                continue;
            }

            $this->paintCell($ctx, $origin, $x, $y, $radius, $core, $color->pack());
        }
    }

    protected function paintCell(
        PaintContext $ctx,
        Point $origin,
        int $x,
        int $y,
        int $centre,
        int $radius,
        int $packed,
    ): void {
        if (! $this->round || $radius < 1) {
            $inset = $centre - $radius;
            $extent = max(1, $radius * 2);
            $ctx->fillRectLocal($x + $inset, $y + $inset, $extent, $extent, $packed);

            return;
        }

        $ctx->fillCircleWorld($origin->x + $x + $centre, $origin->y + $y + $centre, $radius, $packed);
    }

    protected function applyIntrinsicSize(): void
    {
        $count = count($this->pixels);
        $length = ($count === 0) ? 0 : (($count * $this->cell) + (($count - 1) * $this->gap));

        if ($this->axis === Axis::HORIZONTAL) {
            $this->setSize($length, $this->cell);
        } else {
            $this->setSize($this->cell, $length);
        }
    }
}

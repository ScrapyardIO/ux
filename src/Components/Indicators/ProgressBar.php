<?php

namespace ScrapyardIO\UX\Components\Indicators;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\Axis;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * A normalised value drawn as a filled length of a track.
 */
class ProgressBar extends UIComponent
{
    protected float $value = 0.0;

    protected Axis $axis;

    protected Color $track;

    protected Color $fill;

    protected int $thickness;

    protected int $radius;

    public function __construct(float $value = 0.0, Axis $axis = Axis::HORIZONTAL)
    {
        parent::__construct();

        $this->value = $this->clampUnit($value);
        $this->axis = $axis;
        $this->track = Theme::color('track');
        $this->fill = Theme::color('accent');
        $this->thickness = Theme::metric('bar_thickness', 6);
        $this->radius = Theme::metric('radius', 0);

        if ($axis === Axis::HORIZONTAL) {
            $this->setSize($this->thickness * 6, $this->thickness);
        } else {
            $this->setSize($this->thickness, $this->thickness * 6);
        }
    }

    public static function of(float $value, Axis $axis = Axis::HORIZONTAL): static
    {
        return new static($value, $axis);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $this->clampUnit($value);

        return $this;
    }

    public function axis(): Axis
    {
        return $this->axis;
    }

    public function setAxis(Axis $axis): static
    {
        $this->axis = $axis;

        return $this;
    }

    public function setColors(?Color $fill = null, ?Color $track = null): static
    {
        $this->fill = $fill ?? $this->fill;
        $this->track = $track ?? $this->track;

        return $this;
    }

    public function setThickness(int $thickness): static
    {
        $this->thickness = max(1, $thickness);

        return $this;
    }

    public function setRadius(int $radius): static
    {
        $this->radius = max(0, $radius);

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->isEmpty()) {
            return;
        }

        $this->paintBox($ctx, 0, 0, $this->rect->width, $this->rect->height, $this->track);

        $filled = $this->filledExtent();

        if ($filled <= 0) {
            return;
        }

        if ($this->axis === Axis::HORIZONTAL) {
            $this->paintBox($ctx, 0, 0, $filled, $this->rect->height, $this->fill);

            return;
        }

        $this->paintBox($ctx, 0, $this->rect->height - $filled, $this->rect->width, $filled, $this->fill);
    }

    protected function filledExtent(): int
    {
        $extent = $this->axis->extentOf($this->rect->width, $this->rect->height);

        return (int) round($extent * $this->value);
    }

    protected function paintBox(PaintContext $ctx, int $x, int $y, int $width, int $height, Color $color): void
    {
        if ($width <= 0 || $height <= 0 || $color->isTransparent()) {
            return;
        }

        $packed = $color->pack();

        if ($this->radius === 0) {
            $ctx->fillRectLocal($x, $y, $width, $height, $packed);

            return;
        }

        $ctx->fillRoundRectLocal(
            $x,
            $y,
            $width,
            $height,
            min($this->radius, intdiv(min($width, $height), 2)),
            $packed,
        );
    }
}

<?php

namespace ScrapyardIO\UX\Indicators;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A normalised value drawn as a filled length of a track.
 *
 * The value setter is the reason this is a node rather than two `fillRect` calls:
 * setting it reports paint damage over the bar's own box and nothing else, so a
 * bar updating every frame costs one small region per frame instead of a cleared
 * screen. On a paged panel that is the difference between one 20-30 ms page
 * transmit and eight of them.
 */
class ProgressBar extends UXNode
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
    }

    public static function of(float $value, Axis $axis = Axis::HORIZONTAL): static
    {
        return new static($value, $axis);
    }

    public function value(): float
    {
        return $this->value;
    }

    /**
     * A value change is paint damage only — the bar is the same size, only more
     * or less of it is lit.
     */
    public function setValue(float $value): static
    {
        $value = $this->clampUnit($value);

        if ($this->value === $value) {
            return $this;
        }

        $this->value = $value;

        return $this->invalidate();
    }

    public function axis(): Axis
    {
        return $this->axis;
    }

    public function setAxis(Axis $axis): static
    {
        if ($this->axis === $axis) {
            return $this;
        }

        $this->axis = $axis;

        return $this->markNeedsLayout();
    }

    public function setColors(?Color $fill = null, ?Color $track = null): static
    {
        $this->fill = $fill ?? $this->fill;
        $this->track = $track ?? $this->track;

        return $this->invalidate();
    }

    public function setThickness(int $thickness): static
    {
        $thickness = max(1, $thickness);

        if ($this->thickness === $thickness) {
            return $this;
        }

        $this->thickness = $thickness;

        return $this->markNeedsLayout();
    }

    public function setRadius(int $radius): static
    {
        $radius = max(0, $radius);

        if ($this->radius === $radius) {
            return $this;
        }

        $this->radius = $radius;

        return $this->invalidate();
    }

    public function measure(Constraints $constraints): Size
    {
        return $this->spanning($constraints, $this->axis, $this->thickness * 6, $this->thickness);
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();

        if ($box->isEmpty()) {
            return;
        }

        $this->paintBox($surface, 0, 0, $box->width, $box->height, $this->track);

        $filled = $this->filledExtent();

        if ($filled <= 0) {
            return;
        }

        // A vertical bar fills from the bottom, because that is the direction a
        // level reads in — a tank, a meter, a volume.
        if ($this->axis === Axis::HORIZONTAL) {
            $this->paintBox($surface, 0, 0, $filled, $box->height, $this->fill);

            return;
        }

        $this->paintBox($surface, 0, $box->height - $filled, $box->width, $filled, $this->fill);
    }

    public function isOpaque(): bool
    {
        return $this->track->isOpaque() && ($this->radius === 0);
    }

    protected function filledExtent(): int
    {
        $box = $this->localBounds();
        $extent = $this->axis->extentOf($box->width, $box->height);

        return (int) round($extent * $this->value);
    }

    protected function paintBox(DrawingSurface $surface, int $x, int $y, int $width, int $height, Color $color): void
    {
        if (($width <= 0) || ($height <= 0) || $color->isTransparent()) {
            return;
        }

        $packed = $this->packed($color);

        if ($this->radius === 0) {
            $surface->fillRect($x, $y, $width, $height, $packed);

            return;
        }

        $surface->fillRoundRect($x, $y, $width, $height, min($this->radius, intdiv(min($width, $height), 2)), $packed);
    }
}

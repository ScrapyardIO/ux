<?php

namespace ScrapyardIO\UX\Indicators;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A dial: a tick arc with a needle somewhere along it.
 *
 * Drawn as line segments rather than as an arc primitive, because the renderer
 * has no arc and adding one would mean a new drawing primitive for a single
 * node. Segments cost a handful of writes and clip like everything else.
 *
 * The sweep runs from 135 degrees to 405 — the bottom-left of the dial round to
 * the bottom-right — which is the convention an analogue instrument reads in and
 * leaves the bottom of the circle free for a caption.
 */
class Gauge extends UXNode
{
    protected float $value = 0.0;

    protected int $ticks;

    protected int $extent;

    protected Color $scale;

    protected Color $needle;

    protected float $start_degrees = 135.0;

    protected float $sweep_degrees = 270.0;

    public function __construct(float $value = 0.0, int $extent = 32)
    {
        parent::__construct();

        $this->value = $this->clampUnit($value);
        $this->extent = max(4, $extent);
        $this->ticks = max(2, Theme::metric('gauge_ticks', 5));
        $this->scale = Theme::color('outline');
        $this->needle = Theme::color('accent');
    }

    public static function of(float $value, int $extent = 32): static
    {
        return new static($value, $extent);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $value = $this->clampUnit($value);

        if ($this->value === $value) {
            return $this;
        }

        $this->value = $value;

        return $this->invalidate();
    }

    public function setTicks(int $ticks): static
    {
        $ticks = max(2, $ticks);

        if ($this->ticks === $ticks) {
            return $this;
        }

        $this->ticks = $ticks;

        return $this->invalidate();
    }

    public function setColors(?Color $needle = null, ?Color $scale = null): static
    {
        $this->needle = $needle ?? $this->needle;
        $this->scale = $scale ?? $this->scale;

        return $this->invalidate();
    }

    public function setSweep(float $start_degrees, float $sweep_degrees): static
    {
        $this->start_degrees = $start_degrees;
        $this->sweep_degrees = $sweep_degrees;

        return $this->invalidate();
    }

    public function setExtent(int $extent): static
    {
        $extent = max(4, $extent);

        if ($this->extent === $extent) {
            return $this;
        }

        $this->extent = $extent;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        return $this->intrinsic($constraints, $this->extent, $this->extent);
    }

    public function paint(DrawingSurface $surface): void
    {
        $box = $this->localBounds();

        if (($box->width < 3) || ($box->height < 3)) {
            return;
        }

        $centre = new Point(intdiv($box->width - 1, 2), intdiv($box->height - 1, 2));
        $radius = min($centre->x, $centre->y);
        $scale = $this->packed($this->scale);

        for ($tick = 0; $tick < $this->ticks; $tick++) {
            $fraction = $tick / ($this->ticks - 1);
            $outer = $this->pointOn($centre, $radius, $fraction);
            $inner = $this->pointOn($centre, max(1, $radius - max(2, intdiv($radius, 4))), $fraction);

            $surface->drawLine($inner->x, $inner->y, $outer->x, $outer->y, $scale);
        }

        $tip = $this->pointOn($centre, max(1, $radius - 1), $this->value);

        $surface
            ->drawLine($centre->x, $centre->y, $tip->x, $tip->y, $this->packed($this->needle))
            ->fillCircle($centre->x, $centre->y, max(1, intdiv($radius, 8)), $this->packed($this->needle));
    }

    /**
     * Screen y grows downwards, so the sine is added rather than subtracted and
     * the sweep runs clockwise from the start angle.
     */
    protected function pointOn(Point $centre, int $radius, float $fraction): Point
    {
        $radians = deg2rad($this->start_degrees + ($this->sweep_degrees * $this->clampUnit($fraction)));

        return new Point(
            $centre->x + (int) round(cos($radians) * $radius),
            $centre->y + (int) round(sin($radians) * $radius),
        );
    }
}

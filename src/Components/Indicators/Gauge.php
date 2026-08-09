<?php

namespace ScrapyardIO\UX\Components\Indicators;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * A dial: tick marks on an arc with a needle along the sweep.
 */
class Gauge extends UIComponent
{
    protected float $value = 0.0;

    protected int $ticks;

    protected int $extent;

    protected Color $scale;

    protected Color $needle;

    protected float $startDegrees = 135.0;

    protected float $sweepDegrees = 270.0;

    public function __construct(float $value = 0.0, int $extent = 32)
    {
        parent::__construct();

        $this->value = $this->clampUnit($value);
        $this->extent = max(4, $extent);
        $this->ticks = max(2, Theme::metric('gauge_ticks', 5));
        $this->scale = Theme::color('outline');
        $this->needle = Theme::color('accent');
        $this->setSize($this->extent, $this->extent);
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
        $this->value = $this->clampUnit($value);

        return $this;
    }

    public function setTicks(int $ticks): static
    {
        $this->ticks = max(2, $ticks);

        return $this;
    }

    public function setColors(?Color $needle = null, ?Color $scale = null): static
    {
        $this->needle = $needle ?? $this->needle;
        $this->scale = $scale ?? $this->scale;

        return $this;
    }

    public function setSweep(float $startDegrees, float $sweepDegrees): static
    {
        $this->startDegrees = $startDegrees;
        $this->sweepDegrees = $sweepDegrees;

        return $this;
    }

    public function setExtent(int $extent): static
    {
        $this->extent = max(4, $extent);
        $this->setSize($this->extent, $this->extent);

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->width < 3 || $this->rect->height < 3) {
            return;
        }

        $centre = new Point(intdiv($this->rect->width - 1, 2), intdiv($this->rect->height - 1, 2));
        $radius = min($centre->x, $centre->y);
        $scale = $this->scale->pack();
        $needle = $this->needle->pack();
        $origin = $this->worldOrigin();

        for ($tick = 0; $tick < $this->ticks; $tick++) {
            $fraction = $tick / ($this->ticks - 1);
            $outer = $this->pointOn($centre, $radius, $fraction);
            $inner = $this->pointOn($centre, max(1, $radius - max(2, intdiv($radius, 4))), $fraction);

            $ctx->drawLineLocal($inner->x, $inner->y, $outer->x, $outer->y, $scale);
        }

        $tip = $this->pointOn($centre, max(1, $radius - 1), $this->value);
        $ctx->drawLineLocal($centre->x, $centre->y, $tip->x, $tip->y, $needle);
        $ctx->fillCircleWorld(
            $origin->x + $centre->x,
            $origin->y + $centre->y,
            max(1, intdiv($radius, 8)),
            $needle,
        );
    }

    protected function pointOn(Point $centre, int $radius, float $fraction): Point
    {
        $radians = deg2rad($this->startDegrees + ($this->sweepDegrees * $this->clampUnit($fraction)));

        return new Point(
            $centre->x + (int) round(cos($radians) * $radius),
            $centre->y + (int) round(sin($radians) * $radius),
        );
    }
}

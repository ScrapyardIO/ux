<?php

namespace ScrapyardIO\UX\Components\Controls;

use Closure;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\Axis;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Rail with a thumb driven by a normalised 0..1 value.
 */
class Slider extends UIComponent
{
    protected float $value = 0.0;

    protected float $step = 0.05;

    protected Axis $axis;

    protected Color $track;

    protected Color $fill;

    protected Color $thumb;

    protected int $rail;

    protected int $thumbRadius;

    protected bool $focused = false;

    /**
     * @var Closure(float, static): void|null
     */
    protected ?Closure $onChange = null;

    public function __construct(float $value = 0.0, Axis $axis = Axis::HORIZONTAL)
    {
        parent::__construct();

        $this->value = $this->clampUnit($value);
        $this->axis = $axis;
        $this->track = Theme::color('track');
        $this->fill = Theme::color('accent');
        $this->thumb = Theme::color('ink');
        $this->rail = Theme::metric('bar_thickness', 6);
        $this->thumbRadius = Theme::metric('thumb_radius', 4);

        $thickness = max($this->rail, $this->thumbRadius * 2);

        if ($axis === Axis::HORIZONTAL) {
            $this->setSize($this->thumbRadius * 12, $thickness);
        } else {
            $this->setSize($thickness, $this->thumbRadius * 12);
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

    /**
     * @param  Closure(float, static): void  $handler
     */
    public function onChange(Closure $handler): static
    {
        $this->onChange = $handler;

        return $this;
    }

    public function setStep(float $step): static
    {
        $this->step = $this->clampUnit($step);

        return $this;
    }

    public function nudge(float $delta): static
    {
        return $this->commit($this->value + $delta);
    }

    public function setColors(?Color $fill = null, ?Color $track = null, ?Color $thumb = null): static
    {
        $this->fill = $fill ?? $this->fill;
        $this->track = $track ?? $this->track;
        $this->thumb = $thumb ?? $this->thumb;

        return $this;
    }

    public function setRail(int $rail, ?int $thumbRadius = null): static
    {
        $this->rail = max(1, $rail);
        $this->thumbRadius = max(1, $thumbRadius ?? $this->thumbRadius);

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

    public function setFocused(bool $focused): static
    {
        $this->focused = $focused;

        return $this;
    }

    public function isFocused(): bool
    {
        return $this->focused;
    }

    /**
     * Seek to a local coordinate along the rail (for a future input phase).
     */
    public function seekTo(int $localX, int $localY): static
    {
        $horizontal = $this->axis === Axis::HORIZONTAL;
        $length = $horizontal ? $this->rect->width : $this->rect->height;
        $margin = min($this->thumbRadius, intdiv($length, 2));
        $travel = max(1, $length - (2 * $margin));
        $position = ($horizontal ? $localX : $localY) - $margin;
        $fraction = $horizontal ? ($position / $travel) : (1.0 - ($position / $travel));

        return $this->commit($fraction);
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->isEmpty()) {
            return;
        }

        $horizontal = $this->axis === Axis::HORIZONTAL;
        $length = $horizontal ? $this->rect->width : $this->rect->height;
        $thickness = $horizontal ? $this->rect->height : $this->rect->width;
        $margin = min($this->thumbRadius, intdiv($length, 2));
        $travel = max(0, $length - (2 * $margin));
        $offset = $margin + (int) round($travel * $this->value);
        $rail = min($this->rail, $thickness);
        $railOffset = intdiv($thickness - $rail, 2);
        $radius = intdiv($rail, 2);
        $thumbR = max(1, min($this->thumbRadius, intdiv($thickness, 2)));
        $origin = $this->worldOrigin();

        if ($horizontal) {
            $ctx->fillRoundRectLocal(0, $railOffset, $this->rect->width, $rail, $radius, $this->track->pack());
            $ctx->fillRoundRectLocal(0, $railOffset, max(1, $offset), $rail, $radius, $this->fill->pack());
            $ctx->fillCircleWorld($origin->x + $offset, $origin->y + intdiv($this->rect->height, 2), $thumbR, $this->thumb->pack());
        } else {
            $filled = max(1, $this->rect->height - $offset);
            $ctx->fillRoundRectLocal($railOffset, 0, $rail, $this->rect->height, $radius, $this->track->pack());
            $ctx->fillRoundRectLocal($railOffset, $offset, $rail, $filled, $radius, $this->fill->pack());
            $ctx->fillCircleWorld($origin->x + intdiv($this->rect->width, 2), $origin->y + $offset, $thumbR, $this->thumb->pack());
        }

        if ($this->focused) {
            $ctx->drawRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->fill->pack());
        }
    }

    protected function commit(float $value): static
    {
        $before = $this->value;
        $this->setValue($value);

        if ($this->value !== $before && ! is_null($this->onChange)) {
            ($this->onChange)($this->value, $this);
        }

        return $this;
    }
}

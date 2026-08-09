<?php

namespace ScrapyardIO\UX\Components\Controls;

use Closure;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Two-state switch: track with a knob at one end or the other.
 */
class Toggle extends UIComponent
{
    protected bool $on = false;

    protected Color $track;

    protected Color $knob;

    protected Color $active;

    protected int $extent;

    protected bool $focused = false;

    /**
     * @var Closure(bool, static): void|null
     */
    protected ?Closure $onChange = null;

    public function __construct(bool $on = false, ?Closure $onChange = null)
    {
        parent::__construct();

        $this->on = $on;
        $this->track = Theme::color('track');
        $this->knob = Theme::color('ink');
        $this->active = Theme::color('accent');
        $this->extent = Theme::metric('bar_thickness', 6) + 2;
        $this->onChange = $onChange;
        $this->setSize($this->extent * 2, $this->extent);
    }

    public static function of(bool $on = false, ?Closure $onChange = null): static
    {
        return new static($on, $onChange);
    }

    public function isOn(): bool
    {
        return $this->on;
    }

    /**
     * @param  Closure(bool, static): void  $handler
     */
    public function onChange(Closure $handler): static
    {
        $this->onChange = $handler;

        return $this;
    }

    public function setOn(bool $on): static
    {
        if ($this->on === $on) {
            return $this;
        }

        $this->on = $on;

        if (! is_null($this->onChange)) {
            ($this->onChange)($on, $this);
        }

        return $this;
    }

    public function toggle(): static
    {
        return $this->setOn(! $this->on);
    }

    public function setColors(?Color $active = null, ?Color $track = null, ?Color $knob = null): static
    {
        $this->active = $active ?? $this->active;
        $this->track = $track ?? $this->track;
        $this->knob = $knob ?? $this->knob;

        return $this;
    }

    public function setExtent(int $extent): static
    {
        $this->extent = max(3, $extent);
        $this->setSize($this->extent * 2, $this->extent);

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

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->width < 2 || $this->rect->height < 2) {
            return;
        }

        $radius = intdiv($this->rect->height, 2);
        $trackColor = ($this->on ? $this->active : $this->track)->pack();

        $ctx->fillRoundRectLocal(0, 0, $this->rect->width, $this->rect->height, $radius, $trackColor);

        if ($this->focused) {
            $ctx->drawRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->knob->pack());
        }

        $knob = max(1, $radius - 1);
        $centre = $this->on ? ($this->rect->width - $radius - 1) : $radius;
        $origin = $this->worldOrigin();

        $ctx->fillCircleWorld($origin->x + $centre, $origin->y + $radius, $knob, $this->knob->pack());
    }
}

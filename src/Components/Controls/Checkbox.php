<?php

namespace ScrapyardIO\UX\Components\Controls;

use Closure;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Square checkbox with an optional check mark when selected.
 */
class Checkbox extends UIComponent
{
    protected bool $checked = false;

    protected Color $box;

    protected Color $mark;

    protected Color $fill;

    protected int $extent;

    /**
     * @var Closure(bool, static): void|null
     */
    protected ?Closure $onChange = null;

    public function __construct(bool $checked = false, ?Closure $onChange = null)
    {
        parent::__construct();

        $this->checked = $checked;
        $this->box = Theme::color('outline');
        $this->mark = Theme::color('ink');
        $this->fill = Theme::color('accent');
        $this->extent = max(8, Theme::metric('bar_thickness', 6) + 4);
        $this->onChange = $onChange;
        $this->setSize($this->extent, $this->extent);
    }

    public static function of(bool $checked = false, ?Closure $onChange = null): static
    {
        return new static($checked, $onChange);
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): static
    {
        if ($this->checked === $checked) {
            return $this;
        }

        $this->checked = $checked;

        if (! is_null($this->onChange)) {
            ($this->onChange)($checked, $this);
        }

        return $this;
    }

    public function toggle(): static
    {
        return $this->setChecked(! $this->checked);
    }

    /**
     * @param  Closure(bool, static): void  $handler
     */
    public function onChange(Closure $handler): static
    {
        $this->onChange = $handler;

        return $this;
    }

    public function setExtent(int $extent): static
    {
        $this->extent = max(6, $extent);
        $this->setSize($this->extent, $this->extent);

        return $this;
    }

    public function setColors(?Color $mark = null, ?Color $box = null, ?Color $fill = null): static
    {
        $this->mark = $mark ?? $this->mark;
        $this->box = $box ?? $this->box;
        $this->fill = $fill ?? $this->fill;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->isEmpty()) {
            return;
        }

        $ctx->drawRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->box->pack());

        if (! $this->checked) {
            return;
        }

        $inset = max(1, intdiv($this->rect->width, 4));
        $inner = max(1, $this->rect->width - (2 * $inset));

        $ctx->fillRectLocal($inset, $inset, $inner, $inner, $this->fill->pack());

        $x0 = $inset;
        $y0 = intdiv($this->rect->height, 2);
        $x1 = intdiv($this->rect->width, 2) - 1;
        $y1 = $this->rect->height - $inset - 1;
        $x2 = $this->rect->width - $inset - 1;
        $y2 = $inset;

        $ctx->drawLineLocal($x0, $y0, $x1, $y1, $this->mark->pack());
        $ctx->drawLineLocal($x1, $y1, $x2, $y2, $this->mark->pack());
    }
}

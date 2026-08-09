<?php

namespace ScrapyardIO\UX\Components\Controls;

use Closure;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Radio disc belonging to a named group; only one selected per group in a tree.
 */
class Radio extends UIComponent
{
    protected string $group;

    protected bool $selected = false;

    protected Color $ring;

    protected Color $dot;

    protected int $extent;

    /**
     * @var Closure(static): void|null
     */
    protected ?Closure $onSelect = null;

    public function __construct(string $group = 'default', bool $selected = false, ?Closure $onSelect = null)
    {
        parent::__construct();

        $this->group = $group;
        $this->selected = $selected;
        $this->ring = Theme::color('outline');
        $this->dot = Theme::color('accent');
        $this->extent = max(8, Theme::metric('bar_thickness', 6) + 4);
        $this->onSelect = $onSelect;
        $this->setSize($this->extent, $this->extent);
    }

    public static function of(string $group = 'default', bool $selected = false, ?Closure $onSelect = null): static
    {
        return new static($group, $selected, $onSelect);
    }

    public function group(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): static
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * @param  Closure(static): void  $handler
     */
    public function onSelect(Closure $handler): static
    {
        $this->onSelect = $handler;

        return $this;
    }

    /**
     * Select this radio and clear siblings in the same group under a shared parent.
     */
    public function select(): static
    {
        $parent = $this->parent();

        if (! is_null($parent)) {
            foreach ($parent->children() as $sibling) {
                if ($sibling instanceof self && $sibling->group === $this->group) {
                    $sibling->setSelected($sibling === $this);
                }
            }
        } else {
            $this->selected = true;
        }

        if (! is_null($this->onSelect)) {
            ($this->onSelect)($this);
        }

        return $this;
    }

    public function setExtent(int $extent): static
    {
        $this->extent = max(6, $extent);
        $this->setSize($this->extent, $this->extent);

        return $this;
    }

    public function setColors(?Color $dot = null, ?Color $ring = null): static
    {
        $this->dot = $dot ?? $this->dot;
        $this->ring = $ring ?? $this->ring;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->isEmpty()) {
            return;
        }

        $origin = $this->worldOrigin();
        $cx = $origin->x + intdiv($this->rect->width - 1, 2);
        $cy = $origin->y + intdiv($this->rect->height - 1, 2);
        $radius = intdiv(min($this->rect->width, $this->rect->height) - 1, 2);

        $ctx->drawCircleWorld($cx, $cy, $radius, $this->ring->pack());

        if ($this->selected) {
            $ctx->fillCircleWorld($cx, $cy, max(1, intdiv($radius, 2)), $this->dot->pack());
        }
    }
}

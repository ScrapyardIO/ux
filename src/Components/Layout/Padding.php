<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Insets a single child by fixed margins on each edge.
 */
class Padding extends UIComponent
{
    protected int $left;

    protected int $top;

    protected int $right;

    protected int $bottom;

    public function __construct(int $left = 0, int $top = 0, int $right = 0, int $bottom = 0, ?UIComponent $child = null)
    {
        parent::__construct();

        $this->left = max(0, $left);
        $this->top = max(0, $top);
        $this->right = max(0, $right);
        $this->bottom = max(0, $bottom);

        if (! is_null($child)) {
            $this->addChild($child);
        }
    }

    public static function all(int $inset, ?UIComponent $child = null): static
    {
        return new static($inset, $inset, $inset, $inset, $child);
    }

    public static function symmetric(int $horizontal = 0, int $vertical = 0, ?UIComponent $child = null): static
    {
        return new static($horizontal, $vertical, $horizontal, $vertical, $child);
    }

    public function setInsets(int $left, int $top, int $right, int $bottom): static
    {
        $this->left = max(0, $left);
        $this->top = max(0, $top);
        $this->right = max(0, $right);
        $this->bottom = max(0, $bottom);

        return $this;
    }

    public function child(): ?UIComponent
    {
        foreach ($this->children as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                return $child;
            }
        }

        return null;
    }

    public function setChild(?UIComponent $child): static
    {
        $this->clearChildren();

        return is_null($child) ? $this : $this->addChild($child);
    }

    public function layout(Size $available): void
    {
        $child = $this->child();

        if (is_null($child)) {
            if ($this->rect->isEmpty()) {
                $this->setSize($this->left + $this->right, $this->top + $this->bottom);
            }

            return;
        }

        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $inner = $child->size()->isEmpty() ? $available : $child->size();
            $this->setSize(
                $inner->width + $this->left + $this->right,
                $inner->height + $this->top + $this->bottom,
            );
        }

        $innerW = max(0, $this->rect->width - $this->left - $this->right);
        $innerH = max(0, $this->rect->height - $this->top - $this->bottom);

        $child->setPosition($this->left, $this->top);
        $child->setSize($innerW, $innerH);
        $child->layout(new Size($innerW, $innerH));
    }

    protected function draw(PaintContext $ctx): void
    {
        //
    }
}

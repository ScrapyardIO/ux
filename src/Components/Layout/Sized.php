<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Forces a fixed width and/or height around an optional child.
 */
class Sized extends UIComponent
{
    protected ?int $fixedWidth;

    protected ?int $fixedHeight;

    public function __construct(?int $width = null, ?int $height = null, ?UIComponent $child = null)
    {
        parent::__construct();

        $this->fixedWidth = is_null($width) ? null : max(0, $width);
        $this->fixedHeight = is_null($height) ? null : max(0, $height);

        if (! is_null($this->fixedWidth) || ! is_null($this->fixedHeight)) {
            $this->setSize($this->fixedWidth ?? 0, $this->fixedHeight ?? 0);
        }

        if (! is_null($child)) {
            $this->addChild($child);
        }
    }

    public static function square(int $extent, ?UIComponent $child = null): static
    {
        return new static($extent, $extent, $child);
    }

    public static function width(int $width, ?UIComponent $child = null): static
    {
        return new static($width, null, $child);
    }

    public static function height(int $height, ?UIComponent $child = null): static
    {
        return new static(null, $height, $child);
    }

    public function setFixedSize(?int $width, ?int $height): static
    {
        $this->fixedWidth = is_null($width) ? null : max(0, $width);
        $this->fixedHeight = is_null($height) ? null : max(0, $height);

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
        $width = $this->fixedWidth ?? ($this->rect->width > 0 ? $this->rect->width : $available->width);
        $height = $this->fixedHeight ?? ($this->rect->height > 0 ? $this->rect->height : $available->height);

        $child = $this->child();

        if (! is_null($child) && (is_null($this->fixedWidth) || is_null($this->fixedHeight))) {
            $childSize = $child->size();
            $width = $this->fixedWidth ?? $childSize->width;
            $height = $this->fixedHeight ?? $childSize->height;
        }

        $this->setSize($width, $height);

        if (is_null($child)) {
            return;
        }

        $child->setPosition(0, 0);
        $child->setSize($width, $height);
        $child->layout(new Size($width, $height));
    }

    protected function draw(PaintContext $ctx): void
    {
        //
    }
}

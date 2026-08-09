<?php

namespace ScrapyardIO\UX\Components\Layout;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

/**
 * Overlays children at the same origin; paint order is child order (later on top).
 */
class Stack extends UIComponent
{
    public static function of(): static
    {
        return new static();
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $width = $available->width;
            $height = $available->height;

            foreach ($this->children as $child) {
                if (! $child instanceof UIComponent || ! $child->isVisible()) {
                    continue;
                }

                $size = $child->size();
                $width = max($width, $size->width);
                $height = max($height, $size->height);
            }

            $this->setSize($width, $height);
        }

        foreach ($this->children as $child) {
            if (! $child instanceof UIComponent || ! $child->isVisible()) {
                continue;
            }

            if ($child instanceof Positioned) {
                $child->layout(new Size($this->rect->width, $this->rect->height));

                continue;
            }

            $child->setPosition(0, 0);
            $child->layout(new Size($this->rect->width, $this->rect->height));
        }
    }

    protected function draw(PaintContext $ctx): void
    {
        // Children paint themselves.
    }
}

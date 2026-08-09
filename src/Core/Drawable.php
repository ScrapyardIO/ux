<?php

namespace ScrapyardIO\UX\Core;

use ScrapyardIO\UX\Geometry\Rect;

/**
 * Paint-capable tree node. Sprites / particles / overlays extend here.
 *
 * Cull helpers use {@see worldRect()}; invisible or empty cull regions skip paint.
 */
abstract class Drawable extends Node
{
    /**
     * Optional world-space bounds for culling. Null = no cull hint (always attempt paint).
     */
    public function worldRect(): ?Rect
    {
        return null;
    }

    public function paint(PaintContext $ctx): void
    {
        if (! $this->visible) {
            return;
        }

        $world = $this->worldRect();

        if (! is_null($world) && $ctx->cullRect($world)->isEmpty()) {
            return;
        }

        $this->draw($ctx);
        $this->paintDescendants($this, $ctx);
    }

    /**
     * Paint Drawable descendants, walking through non-drawing Node folders.
     */
    protected function paintDescendants(Node $node, PaintContext $ctx): void
    {
        foreach ($node->children() as $child) {
            if (! $child->isVisible()) {
                continue;
            }

            if ($child instanceof Drawable) {
                $child->paint($ctx);

                continue;
            }

            $this->paintDescendants($child, $ctx);
        }
    }

    /**
     * Subclasses paint themselves here; children are painted by {@see paint()}.
     */
    abstract protected function draw(PaintContext $ctx): void;
}

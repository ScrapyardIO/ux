<?php

namespace ScrapyardIO\UX\Chrome;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\EdgeInsets;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\Node;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * An outline drawn around one child, inset by its own thickness.
 *
 * Kept separate from {@see Panel} rather than folded into it as a `borderColor`
 * option, because the two have opposite opacity stories: a panel covers its box
 * and a border covers only its edge. A node that decorates and a node that
 * erases should not be the same node.
 *
 * The stroke is drawn as nested rectangles, one per pixel of thickness, so the
 * whole outline still resolves to segment writes rather than a per-pixel path.
 */
class Border extends UXNode
{
    protected Color $color;

    protected int $thickness;

    protected int $radius;

    public function __construct(?Color $color = null, int $thickness = 1, int $radius = 0, ?Node $child = null)
    {
        parent::__construct();

        $this->color = $color ?? Theme::color('outline');
        $this->thickness = max(1, $thickness);
        $this->radius = max(0, $radius);

        if (! is_null($child)) {
            $this->add($child);
        }
    }

    public static function around(Node $child, ?Color $color = null, int $thickness = 1): static
    {
        return new static($color, $thickness, 0, $child);
    }

    public function color(): Color
    {
        return $this->color;
    }

    public function setColor(Color $color): static
    {
        if ($this->color->equals($color)) {
            return $this;
        }

        $this->color = $color;

        return $this->invalidate();
    }

    public function thickness(): int
    {
        return $this->thickness;
    }

    public function setThickness(int $thickness): static
    {
        $thickness = max(1, $thickness);

        if ($this->thickness === $thickness) {
            return $this;
        }

        $this->thickness = $thickness;

        return $this->markNeedsLayout();
    }

    public function setRadius(int $radius): static
    {
        $radius = max(0, $radius);

        if ($this->radius === $radius) {
            return $this;
        }

        $this->radius = $radius;

        return $this->invalidate();
    }

    public function child(): ?Node
    {
        foreach ($this->children as $child) {
            if ($child->isVisible()) {
                return $child;
            }
        }

        return null;
    }

    public function setChild(?Node $child): static
    {
        foreach ($this->children as $existing) {
            $this->remove($existing);
        }

        return is_null($child) ? $this : $this->add($child);
    }

    /**
     * The offer is deflated rather than loosened, so a border inside a tight box
     * still makes its child fill what is left — otherwise framing a panel would
     * collapse the panel onto its own content.
     */
    public function measure(Constraints $constraints): Size
    {
        $insets = EdgeInsets::all($this->thickness);
        $child = $this->child();

        if (is_null($child)) {
            return $this->stretched($constraints, $insets->horizontal(), $insets->vertical());
        }

        $inner = $child->layout($constraints->deflate($insets));
        $child->placeAt($this->thickness, $this->thickness);

        return $constraints->constrain(new Size(
            $inner->width + $insets->horizontal(),
            $inner->height + $insets->vertical(),
        ));
    }

    public function paint(DrawingSurface $surface): void
    {
        if ($this->color->isTransparent()) {
            return;
        }

        $box = $this->localBounds();
        $packed = $this->packed($this->color);

        for ($ring = 0; $ring < $this->thickness; $ring++) {
            $width = $box->width - (2 * $ring);
            $height = $box->height - (2 * $ring);

            if (($width <= 0) || ($height <= 0)) {
                return;
            }

            if ($this->radius === 0) {
                $surface->drawRect($ring, $ring, $width, $height, $packed);

                continue;
            }

            $surface->drawRoundRect($ring, $ring, $width, $height, max(0, $this->radius - $ring), $packed);
        }
    }
}

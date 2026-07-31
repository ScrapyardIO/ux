<?php

namespace ScrapyardIO\UX\Chrome;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * A filled rectangle that other nodes sit on.
 *
 * The reason to reach for a panel rather than painting a background yourself is
 * {@see isOpaque()}. A panel that really does cover every pixel of its box says
 * so, and the stage then repaints damaged areas starting *from the panel* instead
 * of from the root: the panel restores its own background under a moving child,
 * so erasing costs nothing extra and no full-surface clear is needed per frame.
 *
 * Rounded corners forfeit that, and honestly so — the corners are not painted, so
 * claiming opacity there would leave ghosts exactly where the erase is missing.
 *
 * A panel with children wraps them and an empty one fills what it is offered, so
 * the same class is both a card sized by its contents and a plain backdrop. As
 * the root of a tree it is given the surface tightly, which makes it full-screen
 * whichever of the two it is.
 */
class Panel extends UXNode
{
    protected Color $color;

    protected int $radius;

    public function __construct(?Color $color = null, int $radius = 0)
    {
        parent::__construct();

        $this->color = $color ?? Theme::color('panel');
        $this->radius = max(0, $radius);
    }

    public static function of(Color $color, int $radius = 0): static
    {
        return new static($color, $radius);
    }

    /**
     * A panel in the stage's own background colour, for the root of a tree.
     */
    public static function surface(): static
    {
        return new static(Theme::color('surface'));
    }

    public function color(): Color
    {
        return $this->color;
    }

    /**
     * A colour the surface cannot tell apart from the current one is not a
     * repaint, which is what makes an animated backdrop free on a panel with no
     * shades to animate.
     */
    public function setColor(Color $color): static
    {
        $same = $this->indistinguishable($this->color, $color);

        // Kept either way, so the getter always answers with what was actually
        // asked for rather than the last colour that happened to be damage.
        $this->color = $color;

        return $same ? $this : $this->invalidate();
    }

    public function radius(): int
    {
        return $this->radius;
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

    /**
     * Children keep whatever position they were given rather than being placed
     * here, because a panel is a backdrop and not a layout: a single wrapper
     * child sits at the origin and fills, while manually positioned children —
     * a sketch moving shapes around — stay where the sketch put them.
     */
    public function measure(Constraints $constraints): Size
    {
        $inner = $constraints->loosened();
        $width = 0;
        $height = 0;
        $occupied = false;

        foreach ($this->children as $child) {
            if (! $child->isVisible()) {
                continue;
            }

            $occupied = true;
            $size = $child->layout($inner);
            $origin = $child->bounds();

            $width = max($width, $origin->x + $size->width);
            $height = max($height, $origin->y + $size->height);
        }

        // An empty panel is a fill — a backdrop or a swatch — and has nothing
        // else to take its size from. A panel with children wraps them, because
        // a flex parent offers its children the whole remaining main axis and a
        // greedy card would eat every sibling after it.
        if (! $occupied) {
            return $this->stretched($constraints, 0, 0);
        }

        return $constraints->constrain(new Size($width, $height));
    }

    public function paint(DrawingSurface $surface): void
    {
        if ($this->color->isTransparent()) {
            return;
        }

        $box = $this->localBounds();
        $packed = $this->packed($this->color);

        if ($this->radius === 0) {
            $surface->fillRect(0, 0, $box->width, $box->height, $packed);

            return;
        }

        $surface->fillRoundRect(0, 0, $box->width, $box->height, $this->radius, $packed);
    }

    /**
     * Only when every pixel really is covered. A rounded panel leaves its corners
     * alone and a transparent one paints nothing, and either would leave a ghost
     * if the stage trusted it to erase.
     */
    public function isOpaque(): bool
    {
        return $this->color->isOpaque() && ($this->radius === 0);
    }
}

<?php

namespace ScrapyardIO\UX\Chrome;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\Text\Label;
use ScrapyardIO\UX\UXNode;

/**
 * Three text slots on one line: one pinned left, one centred, one pinned right.
 *
 * A row with SPACE_BETWEEN gets close, but not close enough — the middle slot
 * would drift as the outer two changed width, which is exactly what a status
 * line must not do when it is showing a clock. Placing the three directly keeps
 * the centre anchored to the bar and not to its neighbours.
 *
 * An opaque background makes the bar its own repaint root, so updating the clock
 * damages the bar and nothing above it.
 */
class StatusBar extends UXNode
{
    protected Label $left;

    protected Label $centre;

    protected Label $right;

    protected ?Color $background;

    protected int $padding;

    public function __construct(string $left = '', string $centre = '', string $right = '')
    {
        parent::__construct();

        $this->background = null;
        $this->padding = Theme::metric('gap', 2);

        $this->left = Label::of($left);
        $this->centre = Label::of($centre);
        $this->right = Label::of($right);

        $this->add($this->left, $this->centre, $this->right);
    }

    public static function of(string $left = '', string $centre = '', string $right = ''): static
    {
        return new static($left, $centre, $right);
    }

    public function left(): Label
    {
        return $this->left;
    }

    public function centre(): Label
    {
        return $this->centre;
    }

    public function right(): Label
    {
        return $this->right;
    }

    public function setLeft(string $text): static
    {
        $this->left->setText($text);

        return $this;
    }

    public function setCentre(string $text): static
    {
        $this->centre->setText($text);

        return $this;
    }

    public function setRight(string $text): static
    {
        $this->right->setText($text);

        return $this;
    }

    public function setBackground(?Color $background): static
    {
        $this->background = $background;

        return $this->invalidate();
    }

    public function setPadding(int $padding): static
    {
        $padding = max(0, $padding);

        if ($this->padding === $padding) {
            return $this;
        }

        $this->padding = $padding;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $loose = $constraints->loosened();

        $left = $this->left->layout($loose);
        $centre = $this->centre->layout($loose);
        $right = $this->right->layout($loose);

        $content = max($left->height, $centre->height, $right->height);
        $natural = $left->width + $centre->width + $right->width + (4 * $this->padding);

        $size = $this->spanning(
            $constraints,
            Axis::HORIZONTAL,
            $natural,
            $content + (2 * $this->padding),
        );

        $baseline = intdiv(max(0, $size->height - $content), 2);

        $this->left->placeAt($this->padding, $baseline + intdiv($content - $left->height, 2));
        $this->centre->placeAt(
            max(0, intdiv($size->width - $centre->width, 2)),
            $baseline + intdiv($content - $centre->height, 2),
        );
        $this->right->placeAt(
            max(0, $size->width - $right->width - $this->padding),
            $baseline + intdiv($content - $right->height, 2),
        );

        return $size;
    }

    public function paint(DrawingSurface $surface): void
    {
        if (is_null($this->background) || $this->background->isTransparent()) {
            return;
        }

        $box = $this->localBounds();

        $surface->fillRect(0, 0, $box->width, $box->height, $this->packed($this->background));
    }

    public function isOpaque(): bool
    {
        return ! is_null($this->background) && $this->background->isOpaque();
    }
}

<?php

namespace ScrapyardIO\UX\Text;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Concerns\DrawsText;
use ScrapyardIO\UX\UXNode;

/**
 * Text too wide for its box, scrolled sideways.
 *
 * Scrolling is a paint change and never a layout one: the box is fixed, only the
 * offset inside it moves. That is what keeps a ticker off the layout pass
 * entirely — it damages one band per frame and nothing above it is remeasured.
 *
 * Text that already fits does not scroll at all, and says so through
 * {@see isScrolling()}, so a caller can stop ticking rather than repainting an
 * unchanging box forever.
 */
class Marquee extends UXNode
{
    use DrawsText;

    protected int $offset = 0;

    /**
     * Blank space between the end of the text and the start of its repeat, so
     * the wrap reads as a gap rather than as a collision.
     */
    protected int $separation = 12;

    public function __construct(string $text = '', ?Color $ink = null)
    {
        parent::__construct();

        $this->initialiseText($text, $ink);

        $this->text_alignment = Alignment::centerLeft();
    }

    public static function of(string $text, ?Color $ink = null): static
    {
        return new static($text, $ink);
    }

    public function setSeparation(int $separation): static
    {
        $separation = max(0, $separation);

        if ($this->separation === $separation) {
            return $this;
        }

        $this->separation = $separation;

        return $this->invalidate();
    }

    public function offset(): int
    {
        return $this->offset;
    }

    /**
     * Step the scroll. Unpaced deliberately: one call is one step, so the speed
     * is whatever the sketch's frame loop decides rather than a timer buried in
     * a node.
     */
    public function advance(int $step = 1): static
    {
        if (! $this->isScrolling()) {
            return $this->reset();
        }

        return $this->setOffset($this->offset + $step);
    }

    public function setOffset(int $offset): static
    {
        $cycle = $this->cycle();
        $offset = ($cycle === 0) ? 0 : (($offset % $cycle) + $cycle) % $cycle;

        if ($this->offset === $offset) {
            return $this;
        }

        $this->offset = $offset;

        return $this->invalidate();
    }

    public function reset(): static
    {
        return $this->setOffset(0);
    }

    public function isScrolling(): bool
    {
        return $this->textExtent()->width > $this->bounds()->width;
    }

    /**
     * Fills the width it is offered, because a marquee's whole purpose is to
     * occupy a fixed band; its height is the text's own.
     */
    public function measure(Constraints $constraints): Size
    {
        $extent = $this->measureTextIn($constraints->loosened());

        return $this->spanning($constraints, Axis::HORIZONTAL, $extent->width, $extent->height);
    }

    public function paint(DrawingSurface $surface): void
    {
        if ($this->text === '') {
            return;
        }

        $box = $this->localBounds();

        if (! $this->isScrolling()) {
            $this->paintText($surface, $box, Alignment::centerLeft());

            return;
        }

        $cycle = $this->cycle();

        // Two copies, the second a whole cycle to the right, so the tail of the
        // string and the head of its repeat are both on screen across the wrap.
        // The clip discards whichever parts fall outside the box.
        $this->paintRun($surface, $box, -$this->offset);
        $this->paintRun($surface, $box, $cycle - $this->offset);
    }

    protected function paintRun(DrawingSurface $surface, Rect $box, int $x): void
    {
        $this->paintText(
            $surface,
            new Rect($x, $box->y, $this->textExtent()->width, $box->height),
            Alignment::centerLeft(),
        );
    }

    /**
     * How far the text travels before it repeats.
     */
    protected function cycle(): int
    {
        return $this->textExtent()->width + $this->separation;
    }
}

<?php

namespace ScrapyardIO\UX\Concerns;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use Fabricate\UX\TextMetrics;
use ScrapyardIO\UX\Support\Fonts;
use ScrapyardIO\UX\Support\Theme;

/**
 * Everything a node needs in order to put a run of text somewhere sensible.
 *
 * The awkward part of drawing text here is that measuring it needs a surface, and
 * {@see \Fabricate\UX\Node::measure()} runs before any node has one. The stage
 * lends its renderer for the purpose: `getTextBounds()` writes no pixels, it only
 * reads back the font state the caller just set. A node measured while detached
 * has no stage to ask, so it falls back to the classic font's 6x8 cell — right
 * for the default font and close enough to keep a detached tree's geometry sane.
 *
 * Measurements are cached and thrown away by every setter that could change them,
 * because a readout updating at frame rate would otherwise measure twice a frame
 * for a string whose extent never moves.
 */
trait DrawsText
{
    protected string $text = '';

    /**
     * A registered font name, or null for the built-in classic 5x7.
     */
    protected ?string $font_name = null;

    protected int $text_size = 1;

    protected Color $ink;

    /**
     * Painted behind each glyph cell by the blit itself, which is both cheaper
     * and less flickery than filling the box and then drawing over it.
     */
    protected ?Color $text_background = null;

    protected Alignment $text_alignment;

    /**
     * When set, the text size is chosen during measure as the largest that still
     * fits the offer, rather than being taken from {@see $text_size}.
     */
    protected ?int $fit_ceiling = null;

    /**
     * The size actually used, which differs from the requested one only while
     * auto-fitting.
     */
    protected int $applied_text_size = 1;

    protected ?TextMetrics $metrics = null;

    public function text(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        if ($this->text === $text) {
            return $this;
        }

        return $this->retextTo($text);
    }

    public function setColor(Color $ink): static
    {
        if ($this->ink->equals($ink)) {
            return $this;
        }

        $this->ink = $ink;

        return $this->invalidate();
    }

    public function color(): Color
    {
        return $this->ink;
    }

    /**
     * Null clears the glyph background, leaving whatever is underneath showing
     * through between the strokes.
     */
    public function setTextBackground(?Color $background): static
    {
        $this->text_background = $background;

        return $this->invalidate();
    }

    /**
     * A registered font name. An unregistered name, or a registered stub with no
     * glyph data, silently falls back to classic rather than painting nothing.
     */
    public function setFont(?string $font): static
    {
        if ($this->font_name === $font) {
            return $this;
        }

        $this->font_name = $font;

        return $this->remeasure();
    }

    public function font(): ?string
    {
        return $this->font_name;
    }

    public function setTextSize(int $size): static
    {
        $size = max(1, $size);

        if (($this->text_size === $size) && is_null($this->fit_ceiling)) {
            return $this;
        }

        $this->text_size = $size;
        $this->fit_ceiling = null;

        return $this->remeasure();
    }

    public function textSize(): int
    {
        return $this->applied_text_size;
    }

    /**
     * Pick the largest text size up to $ceiling that still fits the space the
     * parent offers, instead of a fixed one.
     *
     * This is what replaces a sketch scaling coordinates against an imaginary
     * 1024x768 space: scaling *down* to a 128px panel produces a brick wall,
     * whereas choosing a size that fits produces something legible.
     */
    public function fitTextTo(int $ceiling): static
    {
        $ceiling = max(1, $ceiling);

        if ($this->fit_ceiling === $ceiling) {
            return $this;
        }

        $this->fit_ceiling = $ceiling;

        return $this->remeasure();
    }

    public function setTextAlignment(Alignment $alignment): static
    {
        if ($this->text_alignment->equals($alignment)) {
            return $this;
        }

        $this->text_alignment = $alignment;

        return $this->invalidate();
    }

    public function textAlignment(): Alignment
    {
        return $this->text_alignment;
    }

    /**
     * Adopt the library defaults, called from a constructor before any setter
     * runs so the properties are never uninitialised.
     */
    protected function initialiseText(string $text, ?Color $ink = null): void
    {
        $this->text = $text;
        $this->ink = $ink ?? Theme::color('ink');
        $this->font_name = Theme::font();
        $this->text_size = Theme::textSize();
        $this->applied_text_size = $this->text_size;
        $this->text_alignment = Alignment::center();
    }

    /**
     * Swap the text, reporting the cheaper damage whenever the extent did not
     * move — which is the common case for a numeric readout and the difference
     * between a repaint and a relayout every frame.
     */
    protected function retextTo(string $text): static
    {
        $before = $this->textExtent();

        $this->text = $text;
        $this->metrics = null;

        if ($this->textExtent()->equals($before)) {
            return $this->invalidate();
        }

        return $this->markNeedsLayout()->invalidate(Damage::LAYOUT);
    }

    /**
     * A style change can always move the extent, so this is unconditionally a
     * layout change.
     */
    protected function remeasure(): static
    {
        $this->metrics = null;

        return $this->markNeedsLayout()->invalidate(Damage::LAYOUT);
    }

    protected function textExtent(): Size
    {
        return $this->textMetrics()->size;
    }

    protected function textMetrics(): TextMetrics
    {
        return $this->metrics ??= $this->measureText($this->text, $this->applied_text_size);
    }

    /**
     * Resolve the text size against the offer, for a node that auto-fits. Returns
     * the extent at the chosen size.
     */
    protected function measureTextIn(Constraints $constraints): Size
    {
        if (is_null($this->fit_ceiling)) {
            $this->applyTextSize($this->text_size);

            return $this->textExtent();
        }

        for ($size = $this->fit_ceiling; $size > 1; $size--) {
            $extent = $this->measureText($this->text, $size);

            if ($this->fitsWithin($extent->size, $constraints)) {
                $this->applyTextSize($size, $extent);

                return $extent->size;
            }
        }

        $this->applyTextSize(1);

        return $this->textExtent();
    }

    protected function fitsWithin(Size $size, Constraints $constraints): bool
    {
        if ($constraints->hasBoundedWidth() && ($size->width > $constraints->max_width)) {
            return false;
        }

        return ! ($constraints->hasBoundedHeight() && ($size->height > $constraints->max_height));
    }

    protected function applyTextSize(int $size, ?TextMetrics $metrics = null): void
    {
        if ($this->applied_text_size !== $size) {
            $this->applied_text_size = $size;
            $this->metrics = null;
        }

        if (! is_null($metrics)) {
            $this->metrics = $metrics;
        }
    }

    /**
     * Put the renderer into this node's text style. Called before measuring and
     * again before printing, because the renderer's font state is shared by the
     * whole tree and whichever node touched it last wins.
     */
    protected function applyTextStyle(DrawingSurface $surface, ?int $size = null, ?Color $ink = null): void
    {
        $surface
            ->setFont(Fonts::resolve($this->font_name))
            ->setTextSize($size ?? $this->applied_text_size)
            ->setTextWrap(false)
            ->setTextColor(
                $this->packed($ink ?? $this->ink),
                is_null($this->text_background) ? null : $this->packed($this->text_background),
            );
    }

    /**
     * Print $this->text aligned inside $box, correcting for the baseline-relative
     * bounds custom fonts report.
     *
     * The ink can be overridden for the one call, which is how a control paints
     * its label in a different colour while pressed without mutating the state a
     * later frame will read back.
     */
    protected function paintText(
        DrawingSurface $surface,
        Rect $box,
        ?Alignment $alignment = null,
        ?Color $ink = null,
    ): void {
        $this->paintRunOfText($surface, $this->text, $box, $alignment, $ink);
    }

    /**
     * Print an arbitrary run in this node's style, for a node that draws several
     * — a list of rows, say — without wanting a child node per string.
     *
     * Measured through the surface being painted into rather than through the
     * cached metrics, because that surface is the one whose font state was just
     * set.
     */
    protected function paintRunOfText(
        DrawingSurface $surface,
        string $text,
        Rect $box,
        ?Alignment $alignment = null,
        ?Color $ink = null,
    ): void {
        if ($text === '') {
            return;
        }

        $this->applyTextStyle($surface, null, $ink);

        $cursor = TextMetrics::of($surface, $text)->cursorIn($box, $alignment ?? $this->text_alignment);

        $surface->setCursor($cursor->x, $cursor->y)->print($text);
    }

    protected function measureText(string $text, int $size): TextMetrics
    {
        $surface = $this->stage()?->measuringSurface();

        if (is_null($surface)) {
            return $this->estimateText($text, $size);
        }

        $this->applyTextStyle($surface, $size);

        return TextMetrics::of($surface, $text);
    }

    /**
     * The classic font's fixed 6x8 cell, used only when there is no stage to
     * measure through. Exact for the default font, and an honest approximation
     * for anything else — a detached tree is remeasured the moment it is staged.
     */
    protected function estimateText(string $text, int $size): TextMetrics
    {
        return new TextMetrics(
            new Size(6 * strlen($text) * $size, ($text === '') ? 0 : 8 * $size),
            Point::origin(),
        );
    }
}

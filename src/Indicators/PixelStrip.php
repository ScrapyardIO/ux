<?php

namespace ScrapyardIO\UX\Indicators;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Color;
use ScrapyardIO\UX\Support\Theme;
use ScrapyardIO\UX\UXNode;

/**
 * An on-screen mirror of a physical LED strip.
 *
 * Sketches that drive NeoPixels keep hand-rolling this: a row of discs, one per
 * pixel, lit with whatever colour was written to the hardware. Having it as a
 * node means the preview and the strip are set from the same call site, so they
 * cannot drift.
 *
 * Colours arrive as {@see Color} rather than as the packed 0xRRGGBB word a
 * NeoPixel driver takes, so the preview reads correctly on a monochrome panel
 * too — where an unlit pixel is simply dark and a lit one is not.
 */
class PixelStrip extends UXNode
{
    /**
     * @var array<int, Color>
     */
    protected array $pixels = [];

    protected Axis $axis;

    protected int $cell;

    protected int $gap;

    protected bool $round = true;

    protected ?Color $socket;

    public function __construct(int $count = 1, Axis $axis = Axis::HORIZONTAL)
    {
        parent::__construct();

        $this->axis = $axis;
        $this->cell = Theme::metric('pixel_radius', 3) * 2;
        $this->gap = Theme::metric('gap', 2);
        $this->socket = Theme::color('track');

        $this->setCount($count);
    }

    public static function of(int $count, Axis $axis = Axis::HORIZONTAL): static
    {
        return new static($count, $axis);
    }

    public function count(): int
    {
        return count($this->pixels);
    }

    public function setCount(int $count): static
    {
        $count = max(0, $count);

        if (count($this->pixels) === $count) {
            return $this;
        }

        $dark = Color::transparent();
        $pixels = [];

        for ($index = 0; $index < $count; $index++) {
            $pixels[] = $this->pixels[$index] ?? $dark;
        }

        $this->pixels = $pixels;

        return $this->markNeedsLayout();
    }

    public function pixel(int $index): Color
    {
        return $this->pixels[$index] ?? Color::transparent();
    }

    /**
     * Out-of-range indices are ignored rather than throwing: a strip preview is
     * driven by whatever the hardware loop happens to be writing, and a frame is
     * not worth aborting over an off-by-one in a caption.
     */
    public function setPixel(int $index, Color $color): static
    {
        if (! array_key_exists($index, $this->pixels) || $this->pixels[$index]->equals($color)) {
            return $this;
        }

        $this->pixels[$index] = $color;

        return $this->invalidate();
    }

    /**
     * @param  array<int, Color>  $colors
     */
    public function setPixels(array $colors): static
    {
        $changed = false;

        foreach (array_values($colors) as $index => $color) {
            if (! array_key_exists($index, $this->pixels)) {
                continue;
            }

            if ($this->pixels[$index]->equals($color)) {
                continue;
            }

            $this->pixels[$index] = $color;
            $changed = true;
        }

        return $changed ? $this->invalidate() : $this;
    }

    public function clear(): static
    {
        return $this->setPixels(array_fill(0, count($this->pixels), Color::transparent()));
    }

    public function setCell(int $cell, ?int $gap = null): static
    {
        $this->cell = max(1, $cell);
        $this->gap = max(0, $gap ?? $this->gap);

        return $this->markNeedsLayout();
    }

    public function setRound(bool $round = true): static
    {
        if ($this->round === $round) {
            return $this;
        }

        $this->round = $round;

        return $this->invalidate();
    }

    /**
     * The unlit ring drawn behind every pixel, so a dark strip still shows where
     * its pixels are. Null draws nothing behind them.
     */
    public function setSocket(?Color $socket): static
    {
        $this->socket = $socket;

        return $this->invalidate();
    }

    public function measure(Constraints $constraints): Size
    {
        $count = count($this->pixels);
        $length = ($count === 0) ? 0 : (($count * $this->cell) + (($count - 1) * $this->gap));

        return ($this->axis === Axis::HORIZONTAL)
            ? $this->intrinsic($constraints, $length, $this->cell)
            : $this->intrinsic($constraints, $this->cell, $length);
    }

    public function paint(DrawingSurface $surface): void
    {
        $count = count($this->pixels);

        if ($count === 0) {
            return;
        }

        $radius = intdiv($this->cell, 2);
        $socketed = ! is_null($this->socket) && ! $this->socket->isTransparent();

        // A lit pixel sits inside its socket rather than over it, so the socket
        // reads as a bezel. Without the inset the two discs are the same size and
        // the socket is simply never visible.
        $core = $socketed ? max(1, $radius - max(1, intdiv($radius, 3))) : $radius;

        foreach ($this->pixels as $index => $color) {
            $offset = $index * ($this->cell + $this->gap);
            $x = ($this->axis === Axis::HORIZONTAL) ? $offset : 0;
            $y = ($this->axis === Axis::HORIZONTAL) ? 0 : $offset;

            if ($socketed) {
                $this->paintCell($surface, $x, $y, $radius, $radius, $this->packed($this->socket));
            }

            if ($color->isTransparent()) {
                continue;
            }

            $this->paintCell($surface, $x, $y, $radius, $core, $this->packed($color));
        }
    }

    protected function paintCell(
        DrawingSurface $surface,
        int $x,
        int $y,
        int $centre,
        int $radius,
        int $packed,
    ): void {
        if (! $this->round || ($radius < 1)) {
            $inset = $centre - $radius;
            $extent = max(1, $radius * 2);

            $surface->fillRect($x + $inset, $y + $inset, $extent, $extent, $packed);

            return;
        }

        $surface->fillCircle($x + $centre, $y + $centre, $radius, $packed);
    }
}

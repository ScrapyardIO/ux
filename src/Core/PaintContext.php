<?php

namespace ScrapyardIO\UX\Core;

use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Rect;
use ScrapyardIO\Tubes\Rendering\Renderer2D;

/**
 * Borrowed Renderer2D + viewport scroll/clip for one paint pass.
 *
 * bufferRect = worldRect.translated(-scroll) ∩ clip
 */
final class PaintContext
{
    public function __construct(
        public readonly Renderer2D $renderer,
        public readonly Point $scroll,
        public readonly Rect $clip,
        public readonly Point $origin = new Point(0, 0),
    ) {}

    public function withOrigin(Point $origin): self
    {
        return new self($this->renderer, $this->scroll, $this->clip, $origin);
    }

    /**
     * Intersect the current clip with another buffer-space rect (e.g. ScrollView viewport).
     */
    public function withClip(Rect $bufferClip): self
    {
        return new self(
            $this->renderer,
            $this->scroll,
            $this->clip->intersect($bufferClip),
            $this->origin,
        );
    }

    /**
     * World rect → buffer-space intersection with the clip (empty when fully culled).
     */
    public function cullRect(Rect $worldRect): Rect
    {
        $buffer = $worldRect->translated(-$this->scroll->x, -$this->scroll->y);

        return $buffer->intersect($this->clip);
    }

    /**
     * Convert a world point into buffer coordinates.
     */
    public function toBuffer(int $worldX, int $worldY): Point
    {
        return new Point($worldX - $this->scroll->x, $worldY - $this->scroll->y);
    }

    /**
     * Local (component) point → buffer coordinates via current origin.
     */
    public function localToBuffer(int $localX, int $localY): Point
    {
        return $this->toBuffer($this->origin->x + $localX, $this->origin->y + $localY);
    }

    public function fillRectLocal(int $x, int $y, int $w, int $h, int $color): void
    {
        if ($w <= 0 || $h <= 0) {
            return;
        }

        $bx = $this->origin->x + $x - $this->scroll->x;
        $by = $this->origin->y + $y - $this->scroll->y;

        // Hot path: no scroll and fully inside clip — skip Rect allocations.
        if ($this->scroll->x === 0 && $this->scroll->y === 0
            && $bx >= $this->clip->x
            && $by >= $this->clip->y
            && ($bx + $w) <= $this->clip->right()
            && ($by + $h) <= $this->clip->bottom()
        ) {
            $this->renderer->fillRect($bx, $by, $w, $h, $color);

            return;
        }

        $buffer = $this->cullRect(new Rect($this->origin->x + $x, $this->origin->y + $y, $w, $h));

        if ($buffer->isEmpty()) {
            return;
        }

        $this->renderer->fillRect($buffer->x, $buffer->y, $buffer->width, $buffer->height, $color);
    }

    public function fillRoundRectLocal(int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        if ($w <= 0 || $h <= 0) {
            return;
        }

        $world = new Rect($this->origin->x + $x, $this->origin->y + $y, $w, $h);
        $buffer = $this->cullRect($world);

        if ($buffer->isEmpty()) {
            return;
        }

        // Soft path: if cull clipped the round rect, fall back to fillRect for the visible slice.
        if (! $buffer->equals($world->translated(-$this->scroll->x, -$this->scroll->y))) {
            $this->renderer->fillRect($buffer->x, $buffer->y, $buffer->width, $buffer->height, $color);

            return;
        }

        $this->renderer->fillRoundRect($buffer->x, $buffer->y, $buffer->width, $buffer->height, $r, $color);
    }

    public function fillCircleWorld(int $worldX, int $worldY, int $radius, int $color): void
    {
        $bx = $worldX - $this->scroll->x;
        $by = $worldY - $this->scroll->y;

        // Cheap reject against clip (axis-aligned bounds of the circle).
        if (($bx + $radius) < $this->clip->x
            || ($by + $radius) < $this->clip->y
            || ($bx - $radius) >= $this->clip->right()
            || ($by - $radius) >= $this->clip->bottom()
        ) {
            return;
        }

        $this->renderer->fillCircle($bx, $by, $radius, $color);
    }

    public function drawCircleWorld(int $worldX, int $worldY, int $radius, int $color): void
    {
        $bx = $worldX - $this->scroll->x;
        $by = $worldY - $this->scroll->y;

        if (($bx + $radius) < $this->clip->x
            || ($by + $radius) < $this->clip->y
            || ($bx - $radius) >= $this->clip->right()
            || ($by - $radius) >= $this->clip->bottom()
        ) {
            return;
        }

        $this->renderer->drawCircle($bx, $by, $radius, $color);
    }

    public function drawRectLocal(int $x, int $y, int $w, int $h, int $color): void
    {
        if ($w <= 0 || $h <= 0) {
            return;
        }

        $world = new Rect($this->origin->x + $x, $this->origin->y + $y, $w, $h);
        $buffer = $this->cullRect($world);

        if ($buffer->isEmpty()) {
            return;
        }

        $this->renderer->drawRect($buffer->x, $buffer->y, $buffer->width, $buffer->height, $color);
    }

    public function drawLineLocal(int $x0, int $y0, int $x1, int $y1, int $color): void
    {
        $p0 = $this->localToBuffer($x0, $y0);
        $p1 = $this->localToBuffer($x1, $y1);
        $this->renderer->drawLine($p0->x, $p0->y, $p1->x, $p1->y, $color);
    }

    public function printLocal(int $x, int $y, string $text, int $fg, ?int $bg = null, int $size = 1, ?string $font = null): void
    {
        if ($text === '') {
            return;
        }

        $buffer = $this->localToBuffer($x, $y);

        $this->renderer
            ->setFont($font)
            ->setTextSize($size)
            ->setTextWrap(false)
            ->setTextColor($fg, $bg)
            ->setCursor($buffer->x, $buffer->y)
            ->print($text);
    }
}

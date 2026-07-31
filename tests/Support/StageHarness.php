<?php

namespace ScrapyardIO\UX\Tests\Support;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Core\VisualPresentation;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\PageSegmentBuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\UX\Color;
use Fabricate\UX\Node;
use Fabricate\UX\Stage;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;

/**
 * Stages a tree over a real framebuffer and a real renderer.
 *
 * Nothing here is mocked, because what these tests are actually about is what
 * reaches the buffer: which pixels a node put down, and which regions were
 * marked for transmit. A mock renderer would assert that the node called the
 * methods the node was written to call, which is worth nothing.
 */
final class StageHarness
{
    public readonly Stage $stage;

    public readonly Framebuffer $buffer;

    public readonly FormatSpec $spec;

    public function __construct(
        public readonly int $width = 128,
        public readonly int $height = 64,
        ?FormatSpec $spec = null,
    ) {
        $this->spec = $spec ??= self::rgbaSpec();

        // A paged surface cannot be packed row-major, and the page granularity is
        // half the point of testing on one.
        $this->buffer = ($spec->pixel_format === PixelFormat::ROW_MAJOR)
            ? new DirtyRegionsBuffer($width, $height, $spec)
            : new PageSegmentBuffer($width, $height, $spec);

        $this->stage = new Stage(new VisualPresentation(
            new TestDisplay($width, $height, $spec),
            $this->buffer,
            new PhpdafruitGfx($this->buffer),
        ));
    }

    public static function rgbaSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::MSB);
    }

    /**
     * The 1-bit paged layout an SSD1306 uses, where "one colour" means lit or
     * unlit and nothing in between.
     */
    public static function monoSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1);
    }

    public static function mono(int $width = 128, int $height = 64): self
    {
        return new self($width, $height, self::monoSpec());
    }

    /**
     * Stage $root, lay it out, and paint one frame.
     */
    public function paint(Node $root): self
    {
        $this->stage->setRoot($root);
        $this->stage->render();

        return $this;
    }

    /**
     * Paint without presenting, for a test that wants to read the dirty list —
     * presenting flushes the very list it would be reading.
     *
     * @return array<int, Rect> the regions repainted
     */
    public function repaint(): array
    {
        return $this->stage->paintOnly();
    }

    public function pixel(int $x, int $y): int
    {
        return $this->buffer->getPixel($x, $y);
    }

    public function isLit(int $x, int $y): bool
    {
        return $this->pixel($x, $y) !== 0;
    }

    /**
     * Whether a specific declared colour landed at a point.
     *
     * Note this is not the same question as {@see isLit()} on a colour surface:
     * opaque black packs to 0x000000FF there, which is very much a written pixel.
     */
    public function has(Color $color, int $x, int $y): bool
    {
        return $this->pixel($x, $y) === $color->resolveFor($this->spec);
    }

    /**
     * How many pixels hold exactly this colour, which is how a test asks "how
     * much of the box did the node actually cover".
     */
    public function countOf(Color $color): int
    {
        $packed = $color->resolveFor($this->spec);
        $count = 0;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->pixel($x, $y) === $packed) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * The inclusive bounds of every pixel holding $color, or null when it was
     * never painted.
     */
    public function boundsOf(Color $color): ?Rect
    {
        $packed = $color->resolveFor($this->spec);
        $left = PHP_INT_MAX;
        $top = PHP_INT_MAX;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->pixel($x, $y) !== $packed) {
                    continue;
                }

                $left = min($left, $x);
                $top = min($top, $y);
                $right = max($right, $x);
                $bottom = max($bottom, $y);
            }
        }

        return ($right < 0) ? null : Rect::fromBounds($left, $top, $right, $bottom);
    }

    /**
     * The inclusive bounds of every non-zero pixel, or null when nothing was
     * painted. This is how a test asks "where did the ink land" without caring
     * which primitives put it there.
     */
    public function inkBounds(): ?Rect
    {
        $left = PHP_INT_MAX;
        $top = PHP_INT_MAX;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->pixel($x, $y) === 0) {
                    continue;
                }

                $left = min($left, $x);
                $top = min($top, $y);
                $right = max($right, $x);
                $bottom = max($bottom, $y);
            }
        }

        return ($right < 0) ? null : Rect::fromBounds($left, $top, $right, $bottom);
    }

    /**
     * How many pixels are lit, for the assertions that care about coverage
     * rather than shape.
     */
    public function litCount(): int
    {
        $count = 0;

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->pixel($x, $y) !== 0) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Regions the buffer would transmit, as sorted inclusive bounds. Sorted so a
     * test pins behaviour rather than the dirty set's merge order.
     *
     * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    public function dirtyBounds(): array
    {
        $bounds = array_map(
            fn ($update): array => (new Rect(
                $update->origin_x,
                $update->origin_y,
                $update->width,
                $update->height,
            ))->toBounds(),
            $this->buffer->dump(),
        );

        sort($bounds);

        return $bounds;
    }

    /**
     * Mark the buffer transmitted, so the next assertion sees only new damage.
     */
    public function settle(): self
    {
        $this->buffer->flush();

        return $this;
    }
}

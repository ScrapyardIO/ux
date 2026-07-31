<?php

namespace ScrapyardIO\UX\Concerns;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\UX\Color;

/**
 * Turns a declared colour into the packed int a renderer primitive wants.
 *
 * A node is handed a surface, and a surface speaks in packed ints — it has no
 * idea what depth it is packing for. The stage does, so the depth is fetched from
 * there at paint time rather than being frozen into the node when it is built.
 * That is what lets the same subtree be moved between an SSD1306 and an SDL
 * window without touching a single colour.
 */
trait ResolvesColors
{
    /**
     * A null or transparent colour packs to 0, which is both "no ink" and an
     * unlit monochrome pixel.
     */
    protected function packed(?Color $color): int
    {
        return is_null($color) ? 0 : $color->resolveFor($this->formatSpec());
    }

    /**
     * The format of the surface this node will paint into.
     *
     * A detached node has no surface to ask, which happens whenever a subtree is
     * built before being attached. Full-depth RGBA is the right fallback there,
     * because it is the only packing that survives being narrowed later.
     */
    protected function formatSpec(): FormatSpec
    {
        return $this->stage()?->formatSpec() ?? self::detachedFormatSpec();
    }

    /**
     * Whether two colours are the same pixel once this surface has packed them.
     *
     * Repainting is justified by what changes on the glass, not by what changes
     * in the tree. A slow hue wash steps through hundreds of distinct colours
     * that a 1-bit panel renders as the same unlit pixel, and treating each of
     * them as damage would transmit the whole frame for no visible reason. This
     * is what lets a sketch animate a colour unconditionally and let the target
     * decide whether that costs anything.
     *
     * Transparency is compared separately: two colours can pack alike and still
     * differ in whether the node paints at all, or in what it claims from
     * {@see \Fabricate\Contracts\UX\Node::isOpaque()}.
     */
    protected function indistinguishable(?Color $current, ?Color $next): bool
    {
        if (is_null($current) || is_null($next)) {
            return is_null($current) && is_null($next);
        }

        if ($current->equals($next)) {
            return true;
        }

        if (($current->isTransparent() !== $next->isTransparent()) || ($current->isOpaque() !== $next->isOpaque())) {
            return false;
        }

        return $this->packed($current) === $this->packed($next);
    }

    protected static function detachedFormatSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::MSB);
    }
}

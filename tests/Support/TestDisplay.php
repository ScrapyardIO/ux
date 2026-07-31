<?php

namespace ScrapyardIO\UX\Tests\Support;

use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;

/**
 * A display that accepts anything and counts flushes, so a tree can be painted
 * end to end without hardware.
 */
class TestDisplay implements Display
{
    public int $flush_count = 0;

    public function __construct(
        protected int $width,
        protected int $height,
        protected FormatSpec $spec,
    ) {}

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function formatSpec(): FormatSpec
    {
        return $this->spec;
    }

    public function flush(DumpedBuffer $frame): void
    {
        $this->flush_count++;
    }

    public function close(): void {}

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return true;
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return true;
    }
}

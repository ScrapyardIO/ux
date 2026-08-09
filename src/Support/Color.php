<?php

namespace ScrapyardIO\UX\Support;

use InvalidArgumentException;

/**
 * Declared RGBA colour packed to 0xRRGGBBAA for tubes Renderer2D.
 */
final readonly class Color
{
    public function __construct(
        public int $r,
        public int $g,
        public int $b,
        public int $a = 255,
    ) {
        foreach ([$r, $g, $b, $a] as $channel) {
            if ($channel < 0 || $channel > 255) {
                throw new InvalidArgumentException('Color channel must be 0-255.');
            }
        }
    }

    public static function fromHex(string $hex): self
    {
        $value = ltrim(trim($hex), '#');

        if (strlen($value) === 3) {
            $value = $value[0].$value[0].$value[1].$value[1].$value[2].$value[2];
        }

        if (strlen($value) === 6) {
            $value .= 'FF';
        }

        if (strlen($value) !== 8 || ! ctype_xdigit($value)) {
            throw new InvalidArgumentException("Invalid colour hex [{$hex}].");
        }

        return new self(
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2)),
            hexdec(substr($value, 6, 2)),
        );
    }

    public static function white(): self
    {
        return new self(255, 255, 255);
    }

    public static function black(): self
    {
        return new self(0, 0, 0);
    }

    public static function transparent(): self
    {
        return new self(0, 0, 0, 0);
    }

    public function pack(): int
    {
        return (($this->r & 0xFF) << 24)
            | (($this->g & 0xFF) << 16)
            | (($this->b & 0xFF) << 8)
            | ($this->a & 0xFF);
    }

    public function isTransparent(): bool
    {
        return $this->a === 0;
    }

    public function isOpaque(): bool
    {
        return $this->a === 255;
    }

    public function equals(self $other): bool
    {
        return $this->r === $other->r
            && $this->g === $other->g
            && $this->b === $other->b
            && $this->a === $other->a;
    }
}

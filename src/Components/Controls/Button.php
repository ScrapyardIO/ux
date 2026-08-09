<?php

namespace ScrapyardIO\UX\Components\Controls;

use Closure;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Labelled box with pressed/focused visual states. Input wiring comes later.
 */
class Button extends UIComponent
{
    protected string $text;

    protected Color $ink;

    protected Color $face;

    protected Color $focusRing;

    protected int $padding;

    protected int $radius;

    protected int $textSize;

    protected ?string $font;

    protected bool $pressed = false;

    protected bool $focused = false;

    /**
     * @var Closure(static): void|null
     */
    protected ?Closure $onPress = null;

    public function __construct(string $text = '', ?Closure $onPress = null)
    {
        parent::__construct();

        $this->text = $text;
        $this->ink = Theme::color('ink');
        $this->face = Theme::color('panel');
        $this->focusRing = Theme::color('accent');
        $this->padding = Theme::metric('gap', 2) + 1;
        $this->radius = Theme::metric('radius', 0);
        $this->textSize = Theme::textSize();
        $this->font = Theme::font();
        $this->onPress = $onPress;
        $this->applyIntrinsicSize();
    }

    public static function of(string $text, ?Closure $onPress = null): static
    {
        return new static($text, $onPress);
    }

    /**
     * @param  Closure(static): void  $handler
     */
    public function onPress(Closure $handler): static
    {
        $this->onPress = $handler;

        return $this;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        $this->applyIntrinsicSize();

        return $this;
    }

    public function isPressed(): bool
    {
        return $this->pressed;
    }

    public function setPressed(bool $pressed): static
    {
        $this->pressed = $pressed;

        return $this;
    }

    public function isFocused(): bool
    {
        return $this->focused;
    }

    public function setFocused(bool $focused): static
    {
        $this->focused = $focused;

        return $this;
    }

    public function setFace(Color $face): static
    {
        $this->face = $face;

        return $this;
    }

    public function setFocusRing(Color $color): static
    {
        $this->focusRing = $color;

        return $this;
    }

    public function setPadding(int $padding): static
    {
        $this->padding = max(0, $padding);
        $this->applyIntrinsicSize();

        return $this;
    }

    public function setRadius(int $radius): static
    {
        $this->radius = max(0, $radius);

        return $this;
    }

    public function activate(): static
    {
        if (! is_null($this->onPress)) {
            ($this->onPress)($this);
        }

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->rect->isEmpty()) {
            return;
        }

        $face = $this->pressed ? $this->ink : $this->face;
        $ink = $this->pressed ? $this->face : $this->ink;
        $packedFace = $face->pack();

        if ($this->radius === 0) {
            $ctx->fillRectLocal(0, 0, $this->rect->width, $this->rect->height, $packedFace);
        } else {
            $ctx->fillRoundRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->radius, $packedFace);
        }

        if ($this->focused) {
            $ctx->drawRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->focusRing->pack());
        }

        if ($this->text === '' || $ink->isTransparent()) {
            return;
        }

        $textW = 6 * $this->textSize * strlen($this->text);
        $textH = 8 * $this->textSize;
        $x = max(0, intdiv($this->rect->width - $textW, 2));
        $y = max(0, intdiv($this->rect->height - $textH, 2));

        $ctx->printLocal($x, $y, $this->text, $ink->pack(), null, $this->textSize, $this->font);
    }

    protected function applyIntrinsicSize(): void
    {
        $textW = 6 * $this->textSize * strlen($this->text);
        $textH = $this->text === '' ? 0 : 8 * $this->textSize;

        $this->setSize($textW + (2 * $this->padding), $textH + (2 * $this->padding));
    }
}

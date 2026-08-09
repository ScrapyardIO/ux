<?php

namespace ScrapyardIO\UX\Components\Chrome;

use ScrapyardIO\UX\Components\Text\Label;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Three text slots on one line: left, centre, right.
 */
class StatusBar extends UIComponent
{
    protected Label $left;

    protected Label $centre;

    protected Label $right;

    protected ?Color $background;

    protected int $padding;

    public function __construct(string $left = '', string $centre = '', string $right = '')
    {
        parent::__construct();

        $this->background = Theme::color('panel');
        $this->padding = Theme::metric('gap', 2);

        $this->left = Label::of($left);
        $this->centre = Label::of($centre);
        $this->right = Label::of($right);

        $this->addChild($this->left);
        $this->addChild($this->centre);
        $this->addChild($this->right);

        $height = Theme::metric('status_bar_height', 16);
        $this->setSize(0, $height);
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

        return $this;
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0) {
            $this->setSize($available->width, max($this->rect->height, Theme::metric('status_bar_height', 16)));
        }

        $content = max($this->left->size()->height, $this->centre->size()->height, $this->right->size()->height);
        $baseline = intdiv(max(0, $this->rect->height - $content), 2);

        $this->left->setPosition($this->padding, $baseline + intdiv($content - $this->left->size()->height, 2));
        $this->centre->setPosition(
            max(0, intdiv($this->rect->width - $this->centre->size()->width, 2)),
            $baseline + intdiv($content - $this->centre->size()->height, 2),
        );
        $this->right->setPosition(
            max(0, $this->rect->width - $this->right->size()->width - $this->padding),
            $baseline + intdiv($content - $this->right->size()->height, 2),
        );
    }

    protected function draw(PaintContext $ctx): void
    {
        if (is_null($this->background) || $this->background->isTransparent()) {
            return;
        }

        $ctx->fillRectLocal(0, 0, $this->rect->width, $this->rect->height, $this->background->pack());
    }
}

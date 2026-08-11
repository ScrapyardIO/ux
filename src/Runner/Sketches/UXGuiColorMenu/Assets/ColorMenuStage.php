<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXGuiColorMenu\Assets;

use Closure;
use ScrapyardIO\UX\Components\Chrome\Border;
use ScrapyardIO\UX\Components\Chrome\Icon;
use ScrapyardIO\UX\Components\Chrome\Panel;
use ScrapyardIO\UX\Components\Chrome\StatusBar;
use ScrapyardIO\UX\Components\Controls\Button;
use ScrapyardIO\UX\Components\Controls\Menu;
use ScrapyardIO\UX\Components\Text\Label;
use ScrapyardIO\UX\Components\Text\Readout;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\GuiBackdrop;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Theme;

/**
 * Sketch-local GUI card: Menu of backdrop presets, preview swatch, Apply, StatusBar.
 *
 * Layout is explicit (not nested Flex) so the Menu cannot be culled by a zero-height Row.
 * Scene clear owns the window backdrop — only a small swatch Panel is filled.
 *
 * Classic bitmap fonts are ASCII-only; hints must not use Unicode arrows.
 */
class ColorMenuStage extends UIComponent
{
    protected StatusBar $bar;

    protected Border $card;

    protected Panel $cardFace;

    protected Menu $menu;

    protected Button $apply;

    protected Panel $swatch;

    protected Readout $preview;

    protected Label $title;

    protected Label $hint;

    protected Icon $mark;

    protected GuiBackdrop $selected;

    /**
     * @var Closure(GuiBackdrop): void
     */
    protected Closure $onBackdrop;

    /**
     * @param  Closure(GuiBackdrop): void  $onBackdrop
     */
    public function __construct(Closure $onBackdrop, GuiBackdrop $initial = GuiBackdrop::MIDNIGHT)
    {
        parent::__construct('color-menu-stage');

        $this->onBackdrop = $onBackdrop;
        $this->selected = $initial;

        $this->bar = StatusBar::of('ux-gui', $initial->value, strtoupper($initial->hex()));
        $this->bar->setBackground(null);
        $this->bar->left()->setTextSize(2);
        $this->bar->centre()->setTextSize(2);
        $this->bar->right()->setTextSize(2);

        $this->title = Label::of('Backdrop presets', Theme::color('ink'))->setTextSize(2);
        // Decorative accent pip — must stay tiny; Panel must never stretch Icons to full card size.
        $this->mark = Icon::of(IconGlyph::DISC, 12, Theme::color('accent'));

        $this->menu = Menu::of(GuiBackdrop::labels());
        $this->menu->setFocused(true);
        $this->menu->setHighlight(Theme::color('accent'));
        $this->menu->setTextSize(2);
        $this->menu->onChoose(function (string $label): void {
            $backdrop = GuiBackdrop::fromLabel($label);
            if (is_null($backdrop)) {
                return;
            }

            $this->applyBackdrop($backdrop);
        });

        $this->swatch = Panel::of($initial->color());
        $this->swatch->setSize(72, 72);

        $this->preview = Readout::of($initial->value, strtoupper($initial->hex()), 2);

        $this->apply = Button::of('Apply colour', function (): void {
            $label = $this->menu->selected();
            if (is_null($label)) {
                return;
            }

            $backdrop = GuiBackdrop::fromLabel($label);
            if (! is_null($backdrop)) {
                $this->applyBackdrop($backdrop);
            }
        });
        $this->apply->setPadding(8);
        $this->apply->setFace(Theme::color('panel'));
        $this->apply->setFocusRing(Theme::color('accent'));

        $this->hint = Label::of(
            'Arrows move   Enter/Space apply   Esc quit',
            Theme::color('muted'),
        )->setTextSize(1);

        // Semi-transparent-looking face via panel colour — small card only, not full window.
        $this->cardFace = Panel::of(Theme::color('panel'));
        $this->card = Border::around($this->cardFace, Theme::color('outline'), 1);

        $this->cardFace->addChild($this->mark);
        $this->cardFace->addChild($this->title);
        $this->cardFace->addChild($this->menu);
        $this->cardFace->addChild($this->swatch);
        $this->cardFace->addChild($this->preview);
        $this->cardFace->addChild($this->apply);
        $this->cardFace->addChild($this->hint);

        $this->addChild($this->bar);
        $this->addChild($this->card);
    }

    /**
     * @param  Closure(GuiBackdrop): void  $onBackdrop
     */
    public static function of(Closure $onBackdrop, GuiBackdrop $initial = GuiBackdrop::MIDNIGHT): static
    {
        return new static($onBackdrop, $initial);
    }

    public function menu(): Menu
    {
        return $this->menu;
    }

    public function applyButton(): Button
    {
        return $this->apply;
    }

    public function selected(): GuiBackdrop
    {
        return $this->selected;
    }

    public function applyBackdrop(GuiBackdrop $backdrop): void
    {
        $this->selected = $backdrop;
        $this->bar->setCentre($backdrop->value);
        $this->bar->setRight(strtoupper($backdrop->hex()));
        $this->swatch->setColor($backdrop->color());
        $this->preview->setValue($backdrop->value);
        $this->preview->setCaption(strtoupper($backdrop->hex()));
        $this->mark->setColor(Theme::color('accent'));
        ($this->onBackdrop)($backdrop);
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0 || $this->rect->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $barH = 40;
        $this->bar->setPosition(0, 0);
        $this->bar->setSize($this->rect->width, $barH);
        $this->bar->layout($this->bar->size());

        // ~70% of the window — centred card, room for a 5-row menu at textSize 2.
        $cardW = max(360, min(520, (int) round($this->rect->width * 0.72)));
        $cardH = max(280, min(420, (int) round(($this->rect->height - $barH) * 0.78)));
        $cardX = max(0, intdiv($this->rect->width - $cardW, 2));
        $cardY = $barH + max(12, intdiv($this->rect->height - $barH - $cardH, 2));

        $this->card->setPosition($cardX, $cardY);
        $this->card->setSize($cardW, $cardH);
        $this->card->layout($this->card->size());

        $innerW = max(1, $cardW - 2);
        $innerH = max(1, $cardH - 2);
        $pad = 16;
        $gap = 12;

        $this->mark->setExtent(12)->setPosition($pad, $pad);
        $this->title->setPosition($pad + 18, $pad);

        $menuTop = $pad + 28;
        $menuW = max(160, intdiv($innerW - (3 * $pad), 2));
        $rowH = (8 * 2) + (2 * Theme::metric('gap', 2));
        $menuH = $rowH * count(GuiBackdrop::labels());
        $this->menu->setPosition($pad, $menuTop);
        $this->menu->setSize($menuW, $menuH);

        $previewX = $pad + $menuW + $pad;
        $this->swatch->setPosition($previewX, $menuTop);
        $this->swatch->setSize(72, 72);

        $this->preview->setPosition($previewX, $menuTop + 72 + 8);
        // Readout sizes itself; give it a floor width for layout clarity.
        if ($this->preview->size()->width < 80) {
            $this->preview->setSize(120, $this->preview->size()->height);
        }

        $applyY = max($menuTop + $menuH + $gap, $menuTop + 72 + 8 + $this->preview->size()->height + $gap);
        $this->apply->setPosition($pad, $applyY);
        $this->apply->setSize(max($this->apply->size()->width, 140), max($this->apply->size()->height, 28));

        $this->hint->setPosition($pad, min($innerH - $pad - 10, $applyY + $this->apply->size()->height + 8));
    }

    protected function draw(PaintContext $ctx): void
    {
        // Transparent stage — Scene clear is the live backdrop colour.
    }
}

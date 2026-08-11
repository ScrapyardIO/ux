<?php

namespace ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets;

use ScrapyardIO\UX\Components\Ball;
use ScrapyardIO\UX\Components\Chrome\Border;
use ScrapyardIO\UX\Components\Chrome\Icon;
use ScrapyardIO\UX\Components\Chrome\Panel;
use ScrapyardIO\UX\Components\Chrome\StatusBar;
use ScrapyardIO\UX\Components\Indicators\ProgressBar;
use ScrapyardIO\UX\Components\Layout\Column;
use ScrapyardIO\UX\Components\Layout\Padding;
use ScrapyardIO\UX\Components\Layout\Row;
use ScrapyardIO\UX\Components\Layout\Spacer;
use ScrapyardIO\UX\Components\Text\Label;
use ScrapyardIO\UX\Components\Text\Readout;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\Axis;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Color;
use ScrapyardIO\UX\Support\Theme;

/**
 * Sketch-local HUD for {@see \ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\UxCanvasWindowDemo}.
 *
 * Composes catalog widgets. Metal primitives are CPU setSegment writes — keep
 * chrome fills tiny (transparent Panel / StatusBar bg; narrow ProgressBar track).
 */
class DemoHud extends UIComponent
{
    protected StatusBar $bar;

    protected Border $chrome;

    protected Panel $tray;

    protected ProgressBar $boostBar;

    protected Readout $speed;

    protected Label $hint;

    protected Icon $glyph;

    protected int $textSize;

    public function __construct(int $textSize = 2)
    {
        parent::__construct('demo-hud');

        $this->textSize = max(1, $textSize);

        $this->bar = StatusBar::of('FPS', 'UX Scene', 'v');
        $this->bar->left()->setTextSize($this->textSize);
        $this->bar->centre()->setTextSize($this->textSize);
        $this->bar->right()->setTextSize($this->textSize);
        // No full-width StatusBar fillRect after Scene clear (Metal CPU path).
        $this->bar->setBackground(null);

        $this->boostBar = ProgressBar::of(0.0, Axis::HORIZONTAL);
        $this->boostBar->setThickness(8);
        $this->boostBar->setColors(Theme::color('accent'), Theme::color('track'));
        // Fixed narrow track — Expanded-to-window-width would CPU-fill ~800×8/frame.
        $this->boostBar->setSize(160, 8);

        $this->speed = Readout::of('0', 'speed', $this->textSize);
        $this->hint = Label::of('click the ball', Theme::color('muted'))->setTextSize(1);
        $this->glyph = Icon::of(IconGlyph::DISC, 10, Theme::color('accent'));

        $boostRow = Row::of(6);
        $boostRow->addChild($this->boostBar);
        $boostRow->addChild(Spacer::of());

        $meta = Row::of(8);
        $meta->addChild($this->speed);
        $meta->addChild(Spacer::of());
        $meta->addChild($this->hint);
        $meta->addChild($this->glyph);

        $stack = Column::of(4);
        $stack->addChild($boostRow);
        $stack->addChild($meta);

        $padded = Padding::all(6, $stack);
        // Transparent tray: Border + children showcase composition without a wide fillRect.
        $this->tray = Panel::of(Color::transparent());
        $this->tray->addChild($padded);

        $this->chrome = Border::around($this->tray, Theme::color('outline'), 1);

        $this->addChild($this->bar);
        $this->addChild($this->chrome);
    }

    public static function of(int $textSize = 2): static
    {
        return new static($textSize);
    }

    public function bar(): StatusBar
    {
        return $this->bar;
    }

    public function boostBar(): ProgressBar
    {
        return $this->boostBar;
    }

    public function speed(): Readout
    {
        return $this->speed;
    }

    /**
     * Cheap per-frame ProgressBar update (no string / layout churn).
     */
    public function syncBoostBar(Ball $ball): void
    {
        $this->boostBar->setValue($ball->boostUnit());
    }

    /**
     * Push live telemetry from the physics Ball into composed widgets.
     *
     * Call ~10Hz — Metal text + layout are expensive on the CPU segment path.
     *
     * @return bool true when StatusBar / Readout intrinsic sizes likely changed
     */
    public function sync(Ball $ball, float $fps, int $tick): bool
    {
        $boost = $ball->boostRemaining();
        $beforeLeft = $this->bar->left()->size()->width;
        $beforeCentre = $this->bar->centre()->size()->width;
        $beforeRight = $this->bar->right()->size()->width;
        $beforeSpeed = $this->speed->size()->width;

        $this->bar->setLeft(sprintf('FPS %.0f', $fps));
        $this->bar->setCentre($boost > 0.0 ? sprintf('boost %.1fs', $boost) : 'UX Scene');
        $this->bar->setRight(sprintf('v %.0f', $ball->speed()));

        $this->syncBoostBar($ball);
        $this->boostBar->setColors(
            $ball->speed() > 0.0 ? $ball->fillColor() : Theme::color('accent'),
            Theme::color('track'),
        );

        $this->speed->setValue(sprintf('%.0f', $ball->speed()));
        $this->speed->setCaption(sprintf('tick %d  a %.0f/%.0f', $tick, $ball->ax(), $ball->ay()));
        $this->hint->setText($boost > 0.0 ? 'BOOSTING' : 'click the ball');
        $this->glyph->setColor($boost > 0.0 ? Theme::color('warning') : Theme::color('accent'));

        return $beforeLeft !== $this->bar->left()->size()->width
            || $beforeCentre !== $this->bar->centre()->size()->width
            || $beforeRight !== $this->bar->right()->size()->width
            || $beforeSpeed !== $this->speed->size()->width;
    }

    public function layout(Size $available): void
    {
        if ($this->rect->width <= 0) {
            $this->setSize($available->width, $this->rect->height > 0 ? $this->rect->height : 72);
        }

        $barH = max(28, (8 * $this->textSize) + 12);
        $this->bar->setPosition(0, 0);
        $this->bar->setSize($this->rect->width, $barH);

        $trayH = max(40, $this->rect->height - $barH);
        $this->chrome->setPosition(0, $barH);
        $this->chrome->setSize($this->rect->width, $trayH);

        $this->bar->layout($this->bar->size());
        $this->chrome->layout($this->chrome->size());
    }

    protected function draw(PaintContext $ctx): void
    {
        // No full-band wipe — children paint; Labels erase their own glyph boxes.
    }
}

<?php

use ScrapyardIO\Tubes\Canvas\Canvas;
use ScrapyardIO\Tubes\Contracts\Framebuffers\DamageGranularity;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\BitDepth;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\Endianness;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Enums\PixelFormat;
use ScrapyardIO\Tubes\Contracts\Framebuffers\FormatSpec;
use ScrapyardIO\Tubes\Contracts\Framebuffers\Framebuffer as FramebufferContract;
use ScrapyardIO\Tubes\Rendering\SoftRenderer2D;
use ScrapyardIO\UX\Components\Ball;
use ScrapyardIO\UX\Components\Chrome\Icon;
use ScrapyardIO\UX\Components\Chrome\Panel;
use ScrapyardIO\UX\Components\Chrome\StatusBar;
use ScrapyardIO\UX\Components\Text\Label;
use ScrapyardIO\UX\Enums\IconGlyph;
use ScrapyardIO\UX\Core\Drawable;
use ScrapyardIO\UX\Core\Node;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\Scene;
use ScrapyardIO\UX\Geometry\Point;
use ScrapyardIO\UX\Geometry\Rect;
use ScrapyardIO\UX\Support\Theme;

function uxStubFramebuffer(int $w = 128, int $h = 64): FramebufferContract
{
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::MSB);

    return new class($w, $h, $spec) implements FramebufferContract
    {
        /** @var array<int, array<int, int>> */
        private array $grid = [];

        public function __construct(
            private int $width,
            private int $height,
            private FormatSpec $host_format,
        ) {}

        public function viewportWidth(): int
        {
            return $this->width;
        }

        public function viewportHeight(): int
        {
            return $this->height;
        }

        public function hostFormat(): FormatSpec
        {
            return $this->host_format;
        }

        public function getPixel(int $x, int $y): int
        {
            return $this->grid[$y][$x] ?? 0;
        }

        public function setPixel(int $x, int $y, int $value): static
        {
            $this->grid[$y][$x] = $value;

            return $this;
        }

        public function setPixels(array $pixels): static
        {
            foreach ($pixels as [$x, $y, $value]) {
                $this->setPixel($x, $y, $value);
            }

            return $this;
        }

        public function setRegion(array $coordinates, int $value): static
        {
            foreach ($coordinates as [$x, $y]) {
                $this->setPixel($x, $y, $value);
            }

            return $this;
        }

        public function setSegment(int $x, int $y, int $width, int $height, int $color): static
        {
            for ($row = $y; $row < $y + $height; $row++) {
                for ($col = $x; $col < $x + $width; $col++) {
                    $this->setPixel($col, $row, $color);
                }
            }

            return $this;
        }

        public function clear(): static
        {
            return $this->fill(0);
        }

        public function fill(int $color): static
        {
            return $this->setSegment(0, 0, $this->width, $this->height, $color);
        }

        public function blitTo(FramebufferContract $target, int $offset_x = 0, int $offset_y = 0): FramebufferContract
        {
            return $target->blitFrom($this, $offset_x, $offset_y);
        }

        public function blitFrom(FramebufferContract $source, int $offset_x = 0, int $offset_y = 0): FramebufferContract
        {
            for ($y = 0; $y < $source->viewportHeight(); $y++) {
                for ($x = 0; $x < $source->viewportWidth(); $x++) {
                    $this->setPixel($offset_x + $x, $offset_y + $y, $source->getPixel($x, $y));
                }
            }

            return $this;
        }

        public function dump(?int $layer = null): string
        {
            return '';
        }

        public function flush(FormatSpec $spec, bool $as_array = false): string|array
        {
            return $as_array ? [] : '';
        }

        public function damageGranularity(): DamageGranularity
        {
            return DamageGranularity::wholeSurface($this->width, $this->height);
        }

        public function preservesContentsOnPresent(): bool
        {
            return false;
        }
    };
}

function uxStubCanvas(int $w = 128, int $h = 64): Canvas
{
    $fb = uxStubFramebuffer($w, $h);

    return new class($w, $h, $fb) extends Canvas
    {
        public function __construct(
            private int $w,
            private int $h,
            private FramebufferContract $fb,
        ) {}

        public function width(): int
        {
            return $this->w;
        }

        public function height(): int
        {
            return $this->h;
        }

        public function framebuffer(): FramebufferContract
        {
            return $this->fb;
        }

        public function present(): static
        {
            return $this;
        }
    };
}

test('drawable skips invisible nodes', function () {
    $painted = false;

    $node = new class($painted) extends Drawable
    {
        public function __construct(private bool &$painted)
        {
            parent::__construct('ghost');
        }

        protected function draw(PaintContext $ctx): void
        {
            $this->painted = true;
        }
    };

    $node->setVisible(false);
    $node->paint(new PaintContext(new SoftRenderer2D, new Point, new Rect(0, 0, 64, 64)));

    expect($painted)->toBeFalse();
});

test('arena ball physics integrates and bounces on walls', function () {
    Theme::flush();

    $arena = \ScrapyardIO\UX\Components\Arena::of(200, 120, 0.85);
    $ball = Ball::of(10)->enablePhysics(300.0, 0.0);
    $arena->setBall($ball);
    $ball->setCenterF(190.0, 60.0);

    $root = new class extends \ScrapyardIO\UX\Core\UIComponent
    {
        protected function draw(PaintContext $ctx): void {}
    };
    $root->setSize(200, 120);
    $root->addChild($arena);

    $scene = (new Scene)->setRoot($root);
    $scene->process(1.0 / 60.0);

    expect($ball->centerXF())->toBeLessThan(190.0)
        ->and($ball->vx())->toBeLessThan(0.0);
});

test('gui backdrop enum maps labels to colours', function () {
    $labels = \ScrapyardIO\UX\Enums\GuiBackdrop::labels();

    expect($labels)->toContain('Midnight')
        ->and(\ScrapyardIO\UX\Enums\GuiBackdrop::fromLabel('Forest'))->toBe(\ScrapyardIO\UX\Enums\GuiBackdrop::FOREST)
        ->and(\ScrapyardIO\UX\Enums\GuiBackdrop::EMBER->color()->pack())->not->toBe(0);
});

test('color menu stage applies backdrop callback', function () {
    Theme::flush();

    $chosen = null;
    $stage = \ScrapyardIO\UX\Runner\Sketches\UXGuiColorMenu\Assets\ColorMenuStage::of(
        function (\ScrapyardIO\UX\Enums\GuiBackdrop $backdrop) use (&$chosen): void {
            $chosen = $backdrop;
        },
    );

    $stage->setSize(320, 240);
    $stage->layout(new \ScrapyardIO\UX\Geometry\Size(320, 240));
    $stage->menu()->select(2)->choose();

    expect($chosen)->toBe(\ScrapyardIO\UX\Enums\GuiBackdrop::cases()[2])
        ->and($stage->selected())->toBe($chosen);
});

test('panel does not stretch multi-child icons to full face size', function () {
    Theme::flush();

    $icon = Icon::of(IconGlyph::DISC, 12, Theme::color('accent'));
    $label = Label::of('title');
    $panel = Panel::of(Theme::color('panel'));
    $panel->setSize(400, 300);
    $panel->addChild($icon);
    $panel->addChild($label);
    $panel->layout(new \ScrapyardIO\UX\Geometry\Size(400, 300));

    expect($icon->size()->width)->toBe(12)
        ->and($icon->size()->height)->toBe(12);
});

test('demo stage composes hud arena and ball', function () {
    Theme::flush();

    $stage = \ScrapyardIO\UX\Runner\Sketches\UXCanvasWindow\Assets\DemoStage::of();
    $stage->setSize(320, 240);
    $stage->layout(new \ScrapyardIO\UX\Geometry\Size(320, 240));

    expect($stage->children())->toHaveCount(2)
        ->and($stage->arena()->ball())->toBe($stage->ball())
        ->and($stage->sync(60.0, 1))->toBeBool();
});

test('scene paints panel label statusbar and ball through soft renderer', function () {
    Theme::flush();

    $canvas = uxStubCanvas(128, 64);
    $renderer = new SoftRenderer2D;

    $root = Panel::surface();
    $root->setSize(128, 64);
    $root->addChild(StatusBar::of('L', 'C', 'R')->setSize(128, 16));
    $root->addChild(Label::of('hi')->setPosition(4, 20));
    $root->addChild(Ball::of(8)->setCenter(64, 40));

    $folder = new Node('entities');
    $folder->addChild(new Node('npc'));
    $root->addChild($folder);

    $scene = (new Scene)->attach($canvas)->setRoot($root);

    // Copy the object handle before setFramebuffer(&$fb) — unsetFramebuffer nulls the aliased local.
    $pixels = $canvas->framebuffer();
    $bound = $pixels;
    $renderer->setFramebuffer($bound);
    $scene->paint($renderer);
    $renderer->unsetFramebuffer();

    expect($scene->root())->toBe($root)
        ->and($root->children())->toHaveCount(4)
        ->and($pixels->getPixel(0, 0))->not->toBe(0);
});

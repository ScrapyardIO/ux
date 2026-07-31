# ScrapyardIO UX

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/ux.svg)](https://packagist.org/packages/scrapyard-io/ux)
[![License](https://img.shields.io/packagist/l/scrapyard-io/ux.svg)](LICENSE)

UX is the ScrapyardIO node library. A screen is a tree of nodes composed with the
framework's flex-lite layout, painted by a `Stage` that repaints only what
changed, on any surface from a 128x64 SSD1306 to a 1024x768 SDL window.

The point is not that it is prettier than draw calls. It is that a full SSD1306
transmit costs 20-30 ms, which caps an unconditionally redrawn sketch at roughly
30 fps however little of the screen moved. Setters mark damage, the stage paints
the damaged subtrees, and a screen that is standing still costs nothing at all.

## Requirements

- PHP 8.4+
- `scrapyard-io/framework` / `fabricate/*` 0.6.x

## Installation

In the ScrapyardIO monorepo skeleton, require the path package:

```bash
composer require scrapyard-io/ux:^0.6.0
php workshop package:discover
```

Publish the config to change the palette or the default metrics:

```bash
php workshop vendor:publish --provider="ScrapyardIO\UX\UXServiceProvider"
```

## Quick start

Extend `App\Sketches\UXSketch`, build a tree, and mutate it. There is no draw
method and no `present()` call:

```php
use Fabricate\UX\Layout\Align;
use Fabricate\UX\Node;
use ScrapyardIO\UX\Chrome\Panel;
use ScrapyardIO\UX\Text\Label;

class Hello extends UXSketch
{
    protected Label $greeting;

    protected function build(): Node
    {
        $this->greeting = Label::of('hello')->fitTextTo(3);

        return Panel::surface()->add(Align::centered($this->greeting));
    }

    protected function sample(float $dt): void
    {
        $this->greeting->setText(date('H:i:s'));
    }
}
```

The label measures its own glyphs, `Align` centres it, and changing the text to a
string of the same width repaints the label's box and nothing else.

Scaffold a node of your own with:

```bash
php workshop make:node BatteryMeter
```

## The library

| Namespace | Nodes |
|---|---|
| `Text` | `Label`, `Readout`, `Marquee` |
| `Chrome` | `Panel`, `Border`, `Icon`, `StatusBar` |
| `Indicators` | `ProgressBar`, `Gauge`, `Sparkline`, `PixelStrip` |
| `Controls` | `Button`, `Toggle`, `Slider`, `ListView`, `Menu` |

Controls implement the framework's `Focusable`, `Touchable`, `Pointable` and
`Buttonable` contracts, so an `InputRouter` drives the same tree from a
touchscreen, a mouse or a d-pad.

## Colour

Declare colours once as `Fabricate\UX\Color` and let the surface resolve them:

```php
Panel::of(Color::fromHex('#201D2B'));
```

The same tree paints correctly on a 1-bit panel (where every colour collapses to
lit or unlit) and an RGBA window, because packing happens at paint time against
the stage's `FormatSpec` rather than being frozen into the node.

Named colours come from `config/ux.php` through `Support\Theme`, which falls back
to the packaged palette when no application is booted — a node must be
constructible without a container.

## Sizing

Nodes are intrinsic by default: they answer `measure()` with the size they want
inside the offer they were given, and the containers around them decide where
that lands. Two shapes recur enough to be worth knowing:

- `Label::fitTextTo(3)` picks the largest text size up to 3 that still fits,
  which is what makes one tree legible on both a 128px panel and a full window.
- `Column::wrapContent()` makes a group exactly as tall as its children. Without
  it a flex child claims the whole remaining axis and squeezes its siblings.

## Testing

```bash
vendor/bin/pest --testsuite Ux
```

Package tests stage trees over a real framebuffer and a real renderer, and assert
on the pixels written and the regions marked for transmit. See
`tests/Support/StageHarness.php`.

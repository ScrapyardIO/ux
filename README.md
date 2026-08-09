# ScrapyardIO UX

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scrapyard-io/ux.svg)](https://packagist.org/packages/scrapyard-io/ux)
[![License](https://img.shields.io/packagist/l/scrapyard-io/ux.svg)](LICENSE)

UX is the ScrapyardIO **layered scene / node** library for 0.7. A screen is a
tree of `Node` / `Drawable` / `UIComponent` children orchestrated by a `Scene`
that paints through a borrowed tubes `Renderer2D` into a `Canvas` framebuffer.

```text
game-engine (future) → scrapyard-io/ux → scrapyard-io/tubes → framework
```

## Requirements

- PHP 8.4+
- `scrapyard-io/framework` ^0.7
- `scrapyard-io/tubes` ^0.7

## Installation

```bash
composer require scrapyard-io/ux:^0.7.0
php workshop package:discover
```

Publish the config to change the palette or default metrics:

```bash
php workshop vendor:publish --tag=ux-config
```

## Quick start

```php
use ScrapyardIO\UX\Components\Chrome\Panel;
use ScrapyardIO\UX\Components\Chrome\StatusBar;
use ScrapyardIO\UX\Components\Ball;
use ScrapyardIO\UX\Core\Scene;

$root = Panel::surface();
$root->addChild(StatusBar::of('L', 'C', 'R'));
$root->addChild(Ball::of(24)->setCenter(160, 120));

$scene = (new Scene)->attach($window)->setRoot($root);

$renderer->setFramebuffer($window->framebuffer());
$scene->paint($renderer);
$renderer->unsetFramebuffer();
$window->present();
```

## Demo

With UX installed, the tubes smoke name runs the UX Scene sketch:

```bash
./runner canvas-window-demo
./runner canvas-window-demo sdl3 --fps=60
./runner ux-canvas-window-demo   # explicit alias
```

## Generators

```bash
php workshop make:component Hud/Meter
php workshop make:ux-node Entities/Player
```

## Docs

Ecosystem docs and the package `.okf/` knowledge bundle cover the layered tree,
ownership vs tubes, and the engine extension seam.

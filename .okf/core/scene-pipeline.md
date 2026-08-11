---
type: Core
title: Scene pipeline
description: attach Canvas → setRoot → process → paint with borrowed Renderer2D and viewport cull.
resource: src/Core/Scene.php
tags: [core, scene, viewport, paint]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: scene
    resource: src/Core/Scene.php
    title: Scene
  - id: viewport
    resource: src/Core/Viewport.php
    title: Viewport
  - id: paint
    resource: src/Core/PaintContext.php
    title: PaintContext
---

# Flow

1. `Scene::attach(Canvas)` — sync viewport size to canvas.
2. `Scene::setRoot(Node)` — mount tree (`ready()` once).
3. Caller `Renderer2D::setFramebuffer($canvas->framebuffer())`.
4. `Scene::paint($renderer)` — layout UI root, then clear per framebuffer
   `DamageGranularity` (whole-surface → every frame; pixel/dirty → once), paint drawables.
5. Caller `unsetFramebuffer()` + `Canvas::present()` — dirty hosts flush `PARTIAL` dumps.

On dirty panels, widgets must erase their own previous pixels (Ball trail, Label glyph boxes)
so Scene can skip full `fill()` after the first prime. Do **not** wipe full-width HUD bands —
that coalesces with the ball into huge SPI rects on FT232H.

`UxCanvasWindowDemo` may hide the HUD subtree ~10Hz on **pixel/dirty** canvases only.
Whole-surface OSWindow paths clear every paint — HUD must stay visible every frame or
text flickers out between sync ticks.

Viewport scroll is world camera offset. `PaintContext::cullRect()` implements:

`worldRect.translated(-scroll) ∩ clip`.

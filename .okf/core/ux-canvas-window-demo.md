---
type: Core
title: UxCanvasWindowDemo
description: Package sketch canvas-window-demo — UX Scene; default tubes.defaults.canvas (panel or window).
resource: src/Runner/Sketches/UXCanvasWindow/UxCanvasWindowDemo.php
tags: [core, sketch, demo, panel]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-10T23:05:00Z" }
status: draft
sources:
  - id: sketch
    resource: src/Runner/Sketches/UXCanvasWindow/UxCanvasWindowDemo.php
    title: UxCanvasWindowDemo
  - id: flow
    resource: src/Runner/Workflows/UxSceneFlow.php
    title: UxSceneFlow
---

# Sketch

`#[Sketch('canvas-window-demo')]` — `SketchRegistry::replace()` from `UXServiceProvider` so it **takes over** tubes’ sketch when UX is installed. Alias: `ux-canvas-window-demo`.

## Default canvas (critical)

`--profile` has **no CLI default**. With no driver and no `--profile`, the sketch opens `tubes.defaults.canvas` via `CanvasProfiles::locate()`:

| Kind | Flow |
|------|------|
| `panels.*` | `Panel::profile` → `UxSceneFlow::makePanel()` → `OpenPanelNode` |
| `windows.*` | `Window::profile` → `UxSceneFlow::make()` → `OpenWindowNode` |

tubes-dev default: `st7789-front` (phpdafruit + dirty Managed FB). Paint type-hints `Canvas`. PanelIC present packs RGB565.

A hardcoded `--profile=canvas-window-demo` default **must never return** — it forced the OS window path and ignored the panel.

## CLI

```bash
./runner canvas-window-demo                 # tubes.defaults.canvas (ST7789 panel in tubes-dev)
./runner canvas-window-demo sdl3 --fps=60   # OSWindow
./runner canvas-window-demo --profile=canvas-window-demo
```

## Ownership

| Layer | Owner |
|-------|-------|
| open / present / poll / pace | tubes WindowLoop via `UxSceneFlow` |
| Scene tree / paint | UX `Scene` + `DemoStage` |
| Ball physics | catalog `Ball` / `Arena` via `Scene::process` |

## HUD throttle

String HUD sync ~10Hz and `hud()->setVisible(false)` between ticks **only** when
`framebuffer->damageGranularity()` is not whole-surface (PanelIC dirty/SPI).
OSWindow always paints HUD every frame — Scene clears the surface each paint.

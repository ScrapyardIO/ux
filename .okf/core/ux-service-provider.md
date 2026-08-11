---
type: Core
title: UXServiceProvider
description: Merges ux config, publishes ux-config, registers generators and demo sketches.
resource: src/UXServiceProvider.php
tags: [core, provider]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T22:00:00Z" }
status: draft
sources:
  - id: provider
    resource: src/UXServiceProvider.php
    title: UXServiceProvider
---

# Behaviour

- `register()`: `mergeConfigFrom(config/ux.php, 'ux')`
- `boot()`: `Theme::flush()`, register sketches, publish `ux-config`, bind `make:component` + `make:ux-node`

## Sketches

| Binding | Class |
|---------|-------|
| `canvas-window-demo` (replace) + alias `ux-canvas-window-demo` | `Runner\Sketches\UXCanvasWindow\UxCanvasWindowDemo` |
| `ux-gui-color-menu` | `Runner\Sketches\UXGuiColorMenu\UxGuiColorMenu` |

Shared loop: `Runner\Workflows\UxSceneFlow`.

Discovered via `extra.scrapyard-io.providers`.

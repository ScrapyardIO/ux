---
type: Orientation
title: Layered tree
description: Node → Drawable → UIComponent; Scene presents against an attached Canvas.
tags: [orientation, node, drawable, uicomponent, scene]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: node
    resource: src/Core/Node.php
    title: Tree lifecycle base
  - id: drawable
    resource: src/Core/Drawable.php
    title: Paint + cull
  - id: ui
    resource: src/Core/UIComponent.php
    title: Rect layout + hit-test
  - id: scene
    resource: src/Core/Scene.php
    title: Presentation root
---

# Layers

| Layer | Class | Responsibility |
|-------|--------|----------------|
| Tree | `Node` | Parent/children, name, visible, mount, `ready` / `process($dt)` — no pixels |
| Draw | `Drawable` | `paint(PaintContext)`, world cull helpers |
| UI | `UIComponent` | Parent-relative rect, layout, hit-test |
| Present | `Scene` | Root Node, Viewport/scroll, layout+paint against Canvas |

Widgets live under `src/Components/*` and extend `UIComponent`.

# Constraints

- Tree identity and lifecycle stay on `Node`, not `UIComponent`.
- Not every node paints or has a layout rect.
- Window/Canvas types bind only on `Scene` (or an engine subclass).
- Prefer `addChild` composition over deep UI inheritance.

# Related

- [Engine extension seam](engine-extension-seam.md)
- [Scene pipeline](../core/scene-pipeline.md)

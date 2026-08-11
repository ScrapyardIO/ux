---
type: Orientation
title: Engine extension seam
description: Future game-engine package depends on / extends Node, Drawable, and Scene without UI baggage.
tags: [orientation, engine, extension, node]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: node
    resource: src/Core/Node.php
    title: Engine-safe tree base
  - id: drawable
    resource: src/Core/Drawable.php
    title: Paint-capable extension point
  - id: scene
    resource: src/Core/Scene.php
    title: Presentation root subclassable by engine
---

# Seam

```text
game-engine (future) → scrapyard-io/ux → scrapyard-io/tubes → framework
```

| Engine type | Extends |
|-------------|---------|
| Entity / folder roots | `Node` |
| Sprites / particles | `Drawable` |
| Engine Scene | `Scene` (optional subclass) |
| HUD / menus | stay as ux `UIComponent` widgets |

**Do not** bake UX-only naming into the extensible base (`Node` / `Scene`, not `UXNode` as the engine root).
**Do not** put tubes Window/Canvas on every node.
**Do not** require every node to paint or own a layout rect.

# Related

- [Layered tree](layered-tree.md)
- [Dependency direction](../conventions/dependency-direction.md)

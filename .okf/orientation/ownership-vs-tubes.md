---
type: Orientation
title: Ownership vs tubes
description: Canvas owns the framebuffer; Renderer2D borrows it; UX paints through Scene without node buffers.
tags: [orientation, ownership, tubes, framebuffer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: scene
    resource: src/Core/Scene.php
    title: Scene attach/paint orchestration
  - id: paint-ctx
    resource: src/Core/PaintContext.php
    title: Borrowed Renderer2D + cull/clip
---

# Rule

| Owner | Owns |
|-------|------|
| tubes `Canvas` | Framebuffer + present |
| tubes `Renderer2D` | Draw primitives (borrows FB via set/unset) |
| ux `Scene` | Root Node, Viewport/scroll, layout+paint orchestration |
| ux nodes | Tree identity / rects / paint calls — **not** pixel caches |

Paint math:

```text
bufferRect = worldRect.translated(-scroll) ∩ clip
if empty → skip
else draw via Renderer2D in buffer coordinates
```

Never allocate a world-sized buffer for a scrolled scene.[^paint-ctx]

# Related

- [Scene pipeline](../core/scene-pipeline.md)
- [No per-node pixel cache](../traps/no-node-pixel-cache.md)
- [World-sized buffers](../traps/world-sized-buffers.md)

[^scene]: Scene attach/paint orchestration
[^paint-ctx]: Borrowed Renderer2D + cull/clip

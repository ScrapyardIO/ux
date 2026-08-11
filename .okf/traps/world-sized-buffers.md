---
type: Trap
title: World-sized buffers
description: Never allocate a world-sized framebuffer for scroll; cull into the viewport.
tags: [trap, viewport, scroll]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
---

# Trap

A scrolled world larger than the window must **not** allocate a world-sized buffer.

Use Scene/Viewport scroll + `PaintContext::cullRect()` so only the visible slice hits the Canvas framebuffer.

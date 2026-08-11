---
type: Trap
title: Full-window Panel after Scene clear
description: Do not CPU-fillRect a full-window opaque Panel every frame when Scene already clears — Metal primitives are CPU segment writes.
tags: [trap, performance, metal, panel]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T21:05:00Z" }
status: draft
---

# Trap

`Scene::paint()` uses `Renderer2D::fill()` for the backdrop (GPU clear on Metal).

A root `Panel::surface()` then paints the **entire** window via `fillRect` → `setSegment`. On Metal that path is **CPU into the MTLTexture**, so an 800×600 panel is ~480k pixels rewritten every frame after the clear — commonly ~6fps.

**Do:** transparent / non-drawing root + Scene clear color; paint only HUD chrome + sprites.

**Do not:** opaque full-window Panel as a clear substitute on the hot path.

Also avoid **wide** opaque HUD fills on the hot path (StatusBar background / tray Panel / Expanded ProgressBar track across the window). Those are smaller than a full clear but still CPU `setSegment` every frame and can pin the demo around ~25–30fps. Prefer transparent chrome, narrow tracks, and ~10Hz string HUD updates.

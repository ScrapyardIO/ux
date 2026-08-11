---
type: Trap
title: No per-node pixel cache
description: Reject 0.6 Node::$cache on the default path; Canvas owns the only buffer.
tags: [trap, cache, framebuffer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
---

# Trap

0.6 Fabricate UX kept a per-node pixel cache (`Node::$cache`). **0.7 rejects that on the default path.**

Paint into the Canvas framebuffer via borrowed `Renderer2D`. Opt-in `CacheDrawable` may return later if needed — do not restore caches on `Node` / `UIComponent`.

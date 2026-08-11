---
type: Core
title: UX config
description: Palette, metrics, and text defaults under config key ux.
resource: config/ux.php
tags: [core, config]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: config
    resource: config/ux.php
    title: Package config
  - id: theme
    resource: src/Support/Theme.php
    title: Theme reader with offline defaults
---

# Keys

- `ux.palette.*` — hex colours packed to 0xRRGGBBAA
- `ux.metrics.*` — bar thickness, gaps, status bar height, …
- `ux.text.font` / `ux.text.size` — default label face

Publish: `workshop vendor:publish --tag=ux-config`.
`Theme` mirrors defaults so nodes construct without a booted Machine.

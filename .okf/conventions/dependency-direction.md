---
type: Convention
title: Dependency direction
description: game-engine → ux → tubes components; Core is off-limits to non-core; no kitchen-sink umbrellas.
tags: [convention, dependency]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T03:20:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: ux granular tubes/* + fabricate/* requires
  - id: waveforms
    resource: ../../waveforms/composer.json
    title: waveforms granular require pattern
  - id: angel-core
    resource: product-owner-architecture
    title: Angel Core vs component dependency rule 2026-08-11
---

# Direction

```text
game-engine (future) → scrapyard-io/ux → tubes/* + fabricate/*
```

- tubes must **not** depend on ux.
- ux must **not** assume game-engine types.
- Engine extends `Node` / `Drawable` / `Scene`; widgets stay in ux.
- Do not put display/framebuffers back into framework core (tubes ownership).

# Core vs component

UX is outside Tubes Core. It may use **non-core** tubes packages (`tubes/canvas`, `tubes/rendering`, …). It must **not** import `ScrapyardIO\Tubes\Core\*` (or Fabricate/GPIO Core). Same rule as inside the three frameworks: non-core never touches Core.[^angel-core]

# Composer require rule (hard)

Companions list **only** the split packages they import (`tubes/canvas`, `fabricate/sketches`, …).

**Forbidden:** kitchen-sink umbrellas in `require` **or** `suggest` as a workaround when something needs Core. Fix the seam instead.

Gold-standard pattern: `scrapyard-io/waveforms` (`fabricate/*` + `gpio/contracts`, not `scrapyard-io/framework`).

[^angel-core]: Angel Core vs component dependency rule 2026-08-11

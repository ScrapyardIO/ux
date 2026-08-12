---
type: Convention
title: Dependency direction
description: game-engine → ux → tubes components → fabricate components; no kitchen-sink umbrellas in require.
tags: [convention, dependency]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: ux granular tubes/* + fabricate/* requires
  - id: waveforms
    resource: ../../waveforms/composer.json
    title: waveforms granular require pattern
---

# Direction

```text
game-engine (future) → scrapyard-io/ux → tubes/* + fabricate/* → (apps may add umbrella scrapyard-io/tubes)
```

- tubes must **not** depend on ux.
- ux must **not** assume game-engine types.
- Engine extends `Node` / `Drawable` / `Scene`; widgets stay in ux.
- Do not put display/framebuffers back into framework core (tubes ownership).

# Composer require rule (hard)

Companions list **only** the split packages they import (`tubes/canvas`, `fabricate/sketches`, …).

**Forbidden in `require`:** kitchen-sink umbrellas such as `scrapyard-io/framework`, `scrapyard-io/tubes`, `scrapyard-io/gpio-framework`.

**Allowed in `suggest`:** umbrellas when demos need umbrella-only code (today: `Tubes\Core`).

Gold-standard pattern: `scrapyard-io/waveforms` (`fabricate/*` + `gpio/contracts`, not `scrapyard-io/framework`).

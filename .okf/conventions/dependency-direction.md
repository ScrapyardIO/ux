---
type: Convention
title: Dependency direction
description: game-engine → ux → tubes → framework; tubes must not depend on ux.
tags: [convention, dependency]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: ux requires tubes + framework
---

# Direction

```text
game-engine (future) → scrapyard-io/ux → scrapyard-io/tubes → framework
```

- tubes must **not** depend on ux.
- ux must **not** assume game-engine types.
- Engine extends `Node` / `Drawable` / `Scene`; widgets stay in ux.
- Do not put display/framebuffers back into framework core (tubes ownership).

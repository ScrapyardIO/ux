---
type: Convention
title: Companion package
description: Opt-in UX companion above tubes; own provider; not a Fabricate domain.
tags: [convention, companion]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: scrapyard-io/ux identity
---

# Rule

`scrapyard-io/ux` is an **opt-in companion**:

- Own `UXServiceProvider` via `extra.scrapyard-io.providers`.
- Namespace `ScrapyardIO\UX\*`, not `Fabricate\*`.
- Depends on **split** tubes packages for Canvas / Renderer2D (`tubes/canvas`, `tubes/rendering`, …); does not reverse that ownership.
- Never kitchen-sink-require `scrapyard-io/tubes` / `scrapyard-io/framework` — see [dependency direction](dependency-direction.md).

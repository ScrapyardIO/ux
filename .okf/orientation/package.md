---
type: Orientation
title: Package (0.7)
description: scrapyard-io/ux 0.7.0 — layered Scene/Node UX companion above tubes.
resource: .
tags: [orientation, ux, scrapyard-io, 0.7]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package name, version, PHP, autoload, scrapyard-io providers
  - id: agents
    resource: AGENTS.md
    title: Agent package rules
---

# What it is

Composer package `scrapyard-io/ux` at **0.7.0** — ScrapyardIO **node-based UX** companion for 0.7.[^composer]

| Field | Value |
|-------|-------|
| Name | `scrapyard-io/ux` |
| Version | `0.7.0` |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `ScrapyardIO\UX\` → `src/` |
| Discovery | `extra.scrapyard-io.providers` → `UXServiceProvider` |
| Requires | `scrapyard-io/tubes ^0.7`, `scrapyard-io/framework ^0.7` |

# What it is not

- Not `fabricate/ux ^0.6`.
- Not part of slim framework core.
- Not the owner of framebuffers / Canvas / Window (tubes owns those).
- `_src/` is donor-only; runtime code lives under `src/`.

# Related

- [Ownership vs tubes](ownership-vs-tubes.md)
- [Layered tree](layered-tree.md)
- [Dependency direction](../conventions/dependency-direction.md)

[^composer]: Package name, version, PHP, autoload, scrapyard-io providers
[^agents]: Agent package rules

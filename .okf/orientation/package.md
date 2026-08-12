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
| Requires | Granular `tubes/*` + `fabricate/*` only (see below) — **not** kitchen-sink umbrellas |

# Composer requires (granular)

Mirror `scrapyard-io/waveforms`: declare the split packages this tree imports, never the umbrellas.

| Package | Why |
|---------|-----|
| `tubes/canvas` | `Canvas` / `OSWindow` / `PanelIC` |
| `tubes/contracts` | Framebuffer contracts + format enums |
| `tubes/rendering` | `Renderer2D` / `SoftRenderer2D` |
| `tubes/human-input` | `EngineInput` (demo sketches) |
| `tubes/inputs` | `InputHandler` (demo sketches) |
| `tubes/windows` | `WindowHandler` / `WindowException` |
| `tubes/panels` | `PanelException` (demo sketches) |
| `fabricate/contracts` | `SketchRegistry` / sketch attributes |
| `fabricate/nuts-and-bolts` | `AggregateServiceProvider` |
| `fabricate/sketches` | `Sketch` / `Flow` |
| `fabricate/console` | `GeneratorCommand` |

UX must not import `ScrapyardIO\Tubes\Core\*` — Magicalias / CanvasProfiles / WindowLoop need non-core seams (see [dependency direction](../conventions/dependency-direction.md)). Current demo sketch imports of Tubes Core are debt to remove.

# What it is not

- Not `fabricate/ux ^0.6`.
- Not a consumer of kitchen-sink umbrellas.
- Not part of slim framework core.
- Not the owner of framebuffers / Canvas / Window (tubes owns those).
- `_src/` is donor-only; runtime code lives under `src/`.

# Related

- [Ownership vs tubes](ownership-vs-tubes.md)
- [Layered tree](layered-tree.md)
- [Dependency direction](../conventions/dependency-direction.md)

[^composer]: Package name, version, PHP, autoload, scrapyard-io providers
[^agents]: Agent package rules

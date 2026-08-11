---
type: Core
title: Generators
description: make:component and make:ux-node GeneratorCommands.
resource: src/Console
tags: [core, console, generators]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: component-cmd
    resource: src/Console/ComponentMakeCommand.php
    title: make:component
  - id: node-cmd
    resource: src/Console/UxNodeMakeCommand.php
    title: make:ux-node
---

# Commands

| Command | Stub extends | Default namespace |
|---------|--------------|------------------|
| `make:component` | `UIComponent` | `App\Components` |
| `make:ux-node` | `Node` | `App\Nodes` |

`make:ux-node` is deliberately not `make:node` — Flow/Workshop already owns `make:node` for Workflows.

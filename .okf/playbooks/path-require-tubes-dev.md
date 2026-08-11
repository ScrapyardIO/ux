---
type: Playbook
title: Path-require from tubes-dev
description: Symlink scrapyard-io/ux into tubes-dev via path repository.
tags: [playbook, composer, tubes-dev]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T20:45:00Z" }
status: draft
sources:
  - id: root-composer
    resource: ../../../../composer.json
    title: tubes-dev root composer (path scrapyard-io/*)
---

# Steps

1. Checkout lives at `tubes-dev/scrapyard-io/ux`.
2. Root `composer.json` already has `"url": "scrapyard-io/*"` path repo.
3. Require `"scrapyard-io/ux": "^0.7.0"`.
4. `composer update scrapyard-io/ux` from tubes-dev root.
5. Provider discovery picks up `UXServiceProvider`; run `./runner ux-canvas-window-demo`.

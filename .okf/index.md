---
okf_version: "0.2"
---

# scrapyard-io/ux Knowledge Bundle

Package knowledge for `scrapyard-io/ux` (ScrapyardIO layered UX / scene companion, v0.7.0).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** This bundle lives at the **ux package root** only — never under `src/`.
**Links:** Concept cross-links use paths relative to each file.
**Scope:** Document what exists on disk in this 0.7 reconstituting tree, plus durable architecture decisions marked `draft`.
**Dist note:** `.okf/` and root `AGENTS.md` are `export-ignore` in `.gitattributes`.

# Orientation

Section index: [orientation/](orientation/index.md)

* [Package (0.7)](orientation/package.md) - Composer identity, namespace, reconstituting surface.
* [Ownership vs tubes](orientation/ownership-vs-tubes.md) - Canvas owns framebuffer; UX borrows Renderer2D.
* [Layered tree](orientation/layered-tree.md) - Node → Drawable → UIComponent; Scene presents.
* [Engine extension seam](orientation/engine-extension-seam.md) - Future game-engine depends on / extends Node/Drawable/Scene.

# Core

Section index: [core/](core/index.md)

* [UXServiceProvider](core/ux-service-provider.md) - Config merge, publish, generators, demo + GUI sketches.
* [UX config](core/ux-config.md) - Palette / metrics / text; publish tag `ux-config`.
* [Scene pipeline](core/scene-pipeline.md) - attach Canvas, viewport/scroll, layout+paint, cull/clip.
* [Component catalog](core/component-catalog.md) - Ported 0.6 widgets + gaps (ScrollView, Spacer, Ball…).
* [Generators](core/generators.md) - `make:component` / `make:ux-node`.
* [UxCanvasWindowDemo](core/ux-canvas-window-demo.md) - Package demo sketch.

# Conventions

Section index: [conventions/](conventions/index.md)

* [Companion package](conventions/companion-package.md) - Opt-in UX companion above tubes.
* [Dependency direction](conventions/dependency-direction.md) - game-engine → ux → tubes → framework.

# Traps

Section index: [traps/](traps/index.md)

* [No per-node pixel cache](traps/no-node-pixel-cache.md) - Reject 0.6 Node::$cache on the default path.
* [World-sized buffers](traps/world-sized-buffers.md) - Never allocate world-sized framebuffers; cull into viewport.
* [Full-window Panel after Scene clear](traps/fullscreen-panel-after-clear.md) - CPU Metal fillRect of the whole window after GPU clear tanks FPS.

# Playbooks

Section index: [playbooks/](playbooks/index.md)

* [Path-require from tubes-dev](playbooks/path-require-tubes-dev.md) - Local path-repo symlink under tubes-dev.
* [Building UX sketches](playbooks/building-ux-sketches.md) - Standard UxSceneFlow bootstrap.

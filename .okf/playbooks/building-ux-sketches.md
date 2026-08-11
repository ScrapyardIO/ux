---
type: Playbook
title: Building UX sketches
description: Standard UxSceneFlow bootstrap for package and app sketches.
tags: [playbook, sketch, bootstrap]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-09T22:00:00Z" }
status: draft
---

# Building UX sketches

1. `#[Sketch('slug')]` + register via `SketchRegistry`.
2. `Window::profile(...)` / driver override + `Renderer2D`.
3. `UxSceneFlow::make()->run($shared)` with `paint` callback.
4. Inside paint: `frame_t0` → `dt` → input → `Scene::process` → `Scene::paint`.
5. Transparent root + `Scene::setClearColor` (no full-window Panel on Metal).
6. Sketch-local UI under `Runner/Sketches/{Name}/Assets/`.

Website tutorials (Herd ecosystem `scrapyard-io/ux/0.7.x`): Building Sketches, Hello World, Motion, GUI, Human Input.

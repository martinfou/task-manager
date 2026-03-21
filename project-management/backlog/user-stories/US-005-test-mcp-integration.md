---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-005 - Test MCP Project Management Integration

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟢 Low  
**Story Points**: 1  
**Created**: 2026-03-08  
**Updated**: 2026-03-21  
**Assigned Sprint**: Backlog

## Description

Automated verification that the **mcp-project-management** package imports cleanly, **`create_user_story`** writes a user story file and updates the product backlog in an isolated project root, and **`validate_backlog`** can run against bootstrapped `project-management/`.

## User Story

As a maintainer, I want repeatable tests for the MCP server’s Python tools, so that regressions in backlog creation and script wrappers are caught in CI.

## Acceptance Criteria

- [x] Automated tests exercise `create_user_story` in a **temporary** `PROJECT_ROOT` (no writes to the real repo unless explicitly configured).
- [x] Tests confirm the MCP server module imports without starting stdio transport.
- [x] `validate_backlog` tool invocation returns successfully for a bootstrapped backlog tree.
- [x] Documentation: how to run tests (`pip install -e ".[dev]"` + `python -m pytest`).

## Business Value

Reduces risk when changing FastMCP wiring or script wrappers; supports AI clients relying on `create_user_story` and validation tools.

## Technical Requirements

- Optional dev dependency: `pytest`.
- Tests live under `mcp-project-management/tests/`.

## Dependencies

- None (uses bundled `mcp_project_management.data` templates for bootstrap).

## History

- 2026-03-08 — Placeholder story created.
- 2026-03-21 — Implemented pytest integration tests, `pyproject.toml` `[dev]` extra, README testing section, CI workflow.

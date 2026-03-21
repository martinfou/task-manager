---
template_version: 1.1.0
last_updated: 2026-02-14
compatible_with: [sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-002 - Git Initialization and Commit Standards

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 3  
**Created**: 2026-02-14  
**Updated**: 2026-03-21  
**Assigned Sprint**: Sprint 1

## Description

Ensure the project is initialized with Git and establish a mandatory, structured commit message format for all AI assistants to follow. This format requires a business-focused paragraph followed by technical implementation details.

## User Story

As a developer and stakeholder, 
I want commit messages to clearly communicate both the business impact and technical changes of every commit, 
so that the project history is readable for both management and engineering teams.

## Acceptance Criteria

- [x] Project is initialized with `git init` if not already a repository.
- [x] AI configuration files (`.cursorrules`, `.github/copilot-instructions.md`, `.agent/instructions.md`, `.claudecode/instructions.md`) updated with the "Git commit message generation" preset.
- [x] Commit message format includes subject, business paragraph, technical bullets, and optional Refs. Canonical template: `project-management/processes/git-commit-guide.md`.
  - Subject line: `US-XXX:` or `DEF-XXX:` plus short business description (≤72 characters), imperative mood.
  - Body: Business-focused paragraph, then `Technical changes for developers:` with bullet points.
  - Footer: Optional `Refs US-XXX` / `Refs DEF-XXX`.

## Technical Requirements

- Run `test -d .git || git init` in the terminal.
- Update agent files with the exact rules provided in the standard.

## History

- 2026-02-14 - Created and status changed to ⏳ In Progress
- 2026-02-14 - Status changed to ✅ Done after Git initialization and agent config updates
- 2026-03-21 - Re-verified: `git init` at repo root, `commit.template` set, agent configs include git-commit-guide preset; status ✅ Done

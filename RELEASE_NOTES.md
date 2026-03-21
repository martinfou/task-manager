# Release notes

Human-readable summary of what changed in each release. Add a new **dated section** for each merge to `main` or end-of-sprint review.

**How to write an entry**: Follow [project-management/templates/release-note-section-template.md](project-management/templates/release-note-section-template.md) and [project-management/processes/release-notes-process.md](project-management/processes/release-notes-process.md).

**Automation** (optional): `./project-management/scripts/generate-release-notes-draft.sh --auto`

---

## 2026-03-21

### New Features

- **AI Agent Configuration Files** — Added `.cursorrules`, GitHub Copilot, Antigravity, and Claude Code instruction files embedding the project-management workflow and Git commit preset. [US-001](project-management/backlog/user-stories/US-001-ai-agent-configs.md)
- **Git Initialization and Commit Standards** — Repository initialized with Git; commit message format documented in agent configs per git-commit-guide. [US-002](project-management/backlog/user-stories/US-002-init-git-commit-rules.md)
- **Root-Level Docs Folder** — Added `docs/` with README describing purpose versus `project-management/`. [US-003](project-management/backlog/user-stories/US-003-root-docs-folder.md)
- **Root-Level Release Notes** — Added this file and linked processes to the existing section template. [US-004](project-management/backlog/user-stories/US-004-root-release-notes.md)

### Defect Fixes

- **Example defect for demo** — Confirmed DEF-001 as the reference defect for workflow and template demonstration. [DEF-001](project-management/backlog/defects/DEF-001-ui-glitch-fix.md)

### Technical Debt

- (none this release)

### Breaking Changes

- (none this release)

### Migration Notes

- (none this release)

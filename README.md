# task-manager

Repository for the task-manager workspace: a **project-management workflow** (backlog, sprints, processes), supporting **agency agent** definitions, and an optional **MCP server** for AI clients.

## Layout

| Path | Purpose |
|------|---------|
| `project-management/` | Single source of truth for backlog (US-/DEF-/TD-), sprints, processes, templates, scripts, and [INDEX.md](project-management/INDEX.md) for AI entry. |
| `docs/` | General project documentation (architecture, guides) — **not** the PM workflow. See [docs/README.md](docs/README.md). |
| `agency-agents/` | Agent role markdown files for orchestration tools. |
| `mcp-project-management/` | MCP server exposing PM tools and resources. |
| `RELEASE_NOTES.md` | Dated release entries for users and developers. |

## AI-assisted development

- Start from `project-management/INDEX.md`.
- Root agent configs (Cursor, Copilot, Antigravity, Claude Code) summarize the workflow and Git commit standards.

## Git

This repository uses a feature-branch workflow; commit messages follow `project-management/processes/git-commit-guide.md`. Optional local template:

```bash
git config commit.template project-management/templates/git-commit-template.txt
```

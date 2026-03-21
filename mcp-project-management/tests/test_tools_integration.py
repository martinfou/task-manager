"""Integration tests: MCP tools against an isolated temporary project root."""

from __future__ import annotations

import os
from pathlib import Path

import pytest


@pytest.fixture
def isolated_project(tmp_path: Path, monkeypatch: pytest.MonkeyPatch) -> Path:
    """Fresh project with bootstrapped project-management/ (no real repo files touched)."""
    monkeypatch.setenv("PROJECT_ROOT", str(tmp_path))
    from mcp_project_management.bootstrap import ensure_project_management

    msg = ensure_project_management()
    assert "project-management" in msg
    return tmp_path


def test_server_module_imports() -> None:
    """MCP server package loads (stdio server not started)."""
    import mcp_project_management.server  # noqa: F401


def test_create_user_story_writes_file_and_updates_backlog(isolated_project: Path) -> None:
    from mcp_project_management.tools import create_user_story

    result = create_user_story(
        title="MCP integration test story",
        description="Created by pytest to verify create_user_story.",
        acceptance_criteria="File exists on disk\nBacklog row added",
        priority="Low",
        story_points=1,
    )

    assert "Created" in result
    pm = isolated_project / "project-management"
    stories = sorted((pm / "backlog" / "user-stories").glob("US-*.md"))
    assert len(stories) >= 1
    last = stories[-1].read_text(encoding="utf-8")
    assert "MCP integration test story" in last
    pb = (pm / "backlog" / "product-backlog.md").read_text(encoding="utf-8")
    assert "MCP integration test story" in pb


def test_validate_backlog_script_runs(isolated_project: Path) -> None:
    """Wrapper invokes bundled validate-backlog script."""
    from mcp_project_management.tools import validate_backlog

    out = validate_backlog("project-management/backlog")
    assert "pass" in out.lower() or "✓" in out or "validation" in out.lower()

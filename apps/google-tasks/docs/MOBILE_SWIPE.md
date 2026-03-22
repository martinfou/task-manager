# Mobile list-row swipe (US-031)

## Scope (v1)

- **List view only.** Kanban cards do **not** use this swipe layer; they keep on-card **Defer** and **Details** (and drag). This avoids conflicting with column scroll and drag-and-drop.

## When swipe UI is active

Swipe gestures and the “swipe mode” hint apply only when **all** are true:

1. Viewport width **≤ 639px** (`max-width: 639px`).
2. **Coarse** pointer (`pointer: coarse`) and **no hover** (`hover: none`) — typical phones, not most desktops.

Otherwise the **row action buttons** (defer, details, delete) stay visible; no swipe-only path.

## LTR mapping

- **Swipe right** (finger moves right, row follows): reveals **Complete** / **Incomplete** (same toggle as the row checkbox).
- **Swipe left**: reveals **More**; release past the threshold opens the **task actions** sheet (defer presets, move to list, delete).
- **Long-press** (~520 ms) on the row opens the same sheet as swipe-left **More** (accessibility / discovery).

## Scroll vs swipe

The row handler waits for an initial movement decision:

- If vertical movement dominates (`|dy|` wins over `|dx|` by ratio **1.25** after **~12px** vertical or **~14px** horizontal), the gesture is treated as **scroll** and `touchmove` is **not** `preventDefault`’d.
- If horizontal wins, the gesture locks to **horizontal** and subsequent `touchmove` uses `{ passive: false }` and `preventDefault` so the list does not scroll while dragging the row.

Thresholds live in `TaskListRowSwipe.vue` (`VERTICAL_LOCK_DY`, `HORIZONTAL_LOCK_DX`, `RATIO`, `SNAP_PX`).

## Delete safety

**Delete** from the swipe sheet uses the same flow as the row **Delete** button: **undo toast** (see US-029), not a separate confirmation modal.

## RTL

Swipe directions are **LTR-only** until the app supports mirrored layout; see the user story Notes.

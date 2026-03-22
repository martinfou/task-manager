# Undo toast (US-029)

After **complete**, **single-task delete**, or **single-task bulk move** (exactly one row moved), the Tasks UI shows a **non-blocking toast** with **Undo**.

## Timing

- Duration defaults from `GOOGLE_TASKS_UNDO_TOAST_DELAY_MS` in [config/google-tasks.php](../config/google-tasks.php) (see `.env.example`).
- Authenticated users can override with **Profile → Tasks** (`users.undo_toast_delay_ms`); allowed values are fixed in `ProfileController` (e.g. 3s–30s).

## Delete behavior

- **Delete** is **optimistic** in the list: the row disappears immediately.
- The HTTP **DELETE** to Google runs when the toast timer **expires**, unless the user clicks **Undo** (task is restored in the UI without calling Google).
- If the API rejects delete after commit, the normal error banner applies; there is **no** automatic restore of the row (Google is source of truth once committed).

## Complete and move

- **Complete**: PATCH runs immediately; **Undo** sends a second PATCH to `needsAction`.
- **Move** (single selection): POST runs immediately; **Undo** POSTs back to the source list.

## Stacking

- **One toast at a time.** A new undo-eligible action **flushes** any pending deferred delete (runs `onCommit`) before showing the next toast, so the previous delete is finalized. Documented so operators know rapid deletes are not all undoable in parallel.

## Out of scope (v1)

- **Bulk delete** and **multi-task bulk move** do **not** show an undo toast.
- **Bulk complete** does not show an undo toast.

## Polling

- While a deferred delete is pending, background **poll** is **held** (`holdPolling` in `useUndoToast`) so the removed task is not immediately refetched from Google.

## Accessibility

- Toast uses `role="status"` and `aria-live="polite"`.
- **Undo** is a focusable button.

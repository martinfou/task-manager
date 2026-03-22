# Snooze / defer presets (US-030)

“Snooze” is implemented by **updating the task due date** via the same PATCH endpoint as the task inspector.

## Time zones

Preset math runs in the **browser’s local timezone** (same as the `<input type="datetime-local">` control in the inspector). The **Today** view on the server uses `config('app.timezone')` in Laravel; if the device timezone differs from that setting, “today” in the app header and preset “tomorrow” may not align perfectly.

## Presets

| Preset | Due set to |
|--------|------------|
| **Tomorrow** | Next calendar day. If the task **already had** a due datetime, the **local clock time** is kept on that new day. If it had **no** due, **09:00 local** is used. |
| **Next week** | **Monday 00:00 local** at the start of the **ISO week immediately after** the week that contains “now” (if today is Monday, this is Monday **+7 days**, not today). |
| **Weekend** | The **next** **Saturday 09:00 local** strictly after “now”: if today is Saturday **before** 09:00, use **today** at 09:00; if Saturday **after** 09:00 or any other weekday, use the **following** Saturday 09:00. |
| **Pick date…** | Opens or focuses the task inspector and focuses the due field (`#detail-edit-due`). |

Mapping logic lives in `resources/js/utils/deferPresets.js` (unit-tested).

## Google Tasks API

Due values are sent as RFC 3339 / ISO 8601 strings in the PATCH body, consistent with the existing task update flow.

## Undo

If the task **had** a due date before the preset, an undo toast can restore the previous due. Undo is **not** offered when there was no prior due (the API does not clear due via `null` in the current controller mapping).

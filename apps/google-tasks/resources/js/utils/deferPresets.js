/**
 * Snooze / defer presets (US-030). All calculations use the browser local timezone
 * (align with Laravel `config('app.timezone')` when server renders Today — document in docs).
 */

/**
 * Monday 00:00 local at the start of the ISO week that contains `d`, then add 7 days
 * → Monday 00:00 of the **next** calendar week after the one containing today.
 *
 * @param {Date} d
 * @returns {Date}
 */
export function startOfNextIsoWeekMondayMidnight(d = new Date()) {
    const x = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const dow = x.getDay();
    const diffToMonday = dow === 0 ? -6 : 1 - dow;
    x.setDate(x.getDate() + diffToMonday);
    x.setHours(0, 0, 0, 0);
    x.setDate(x.getDate() + 7);
    return x;
}

/**
 * Next Saturday 09:00 local per US-030 Notes:
 * Saturday before 09:00 → today 09:00; Saturday after 09:00 → +7d;
 * Sun–Fri → upcoming Saturday 09:00.
 *
 * @param {Date} now
 * @returns {Date}
 */
export function nextSaturdayNineLocal(now = new Date()) {
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const dow = d.getDay();
    if (dow === 6) {
        const atNine = new Date(
            d.getFullYear(),
            d.getMonth(),
            d.getDate(),
            9,
            0,
            0,
            0,
        );
        if (now.getTime() < atNine.getTime()) {
            return atNine;
        }
        return new Date(
            d.getFullYear(),
            d.getMonth(),
            d.getDate() + 7,
            9,
            0,
            0,
            0,
        );
    }
    const daysUntilSat = (6 - dow + 7) % 7;
    const add = daysUntilSat === 0 ? 7 : daysUntilSat;
    return new Date(
        d.getFullYear(),
        d.getMonth(),
        d.getDate() + add,
        9,
        0,
        0,
        0,
    );
}

/**
 * Calendar tomorrow; if `previousDue` parses, preserve local time on that day;
 * otherwise 09:00 local (documented default for tasks without a due).
 *
 * @param {Date} now
 * @param {string | null | undefined} previousDueIso
 * @returns {Date}
 */
export function tomorrowDeferLocal(now = new Date(), previousDueIso) {
    const tomorrow = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate() + 1,
    );
    if (previousDueIso) {
        const prev = new Date(previousDueIso);
        if (!Number.isNaN(prev.getTime())) {
            tomorrow.setHours(
                prev.getHours(),
                prev.getMinutes(),
                prev.getSeconds(),
                prev.getMilliseconds(),
            );
            return tomorrow;
        }
    }
    tomorrow.setHours(9, 0, 0, 0);
    return tomorrow;
}

/**
 * @param {'tomorrow' | 'nextWeek' | 'weekend'} preset
 * @param {Date} [now]
 * @param {string | null | undefined} [previousDueIso]
 * @returns {string} RFC3339 for Google Tasks `due`
 */
export function computeDeferDueIso(preset, now = new Date(), previousDueIso) {
    let d;
    if (preset === 'tomorrow') {
        d = tomorrowDeferLocal(now, previousDueIso);
    } else if (preset === 'nextWeek') {
        d = startOfNextIsoWeekMondayMidnight(now);
    } else if (preset === 'weekend') {
        d = nextSaturdayNineLocal(now);
    } else {
        throw new Error(`Unknown defer preset: ${preset}`);
    }
    return d.toISOString();
}

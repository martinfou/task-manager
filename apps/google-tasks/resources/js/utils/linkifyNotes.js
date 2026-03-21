/**
 * Split plain text into segments for safe link rendering (http/https only).
 * @param {string|null|undefined} text
 * @returns {Array<{ type: 'text', value: string } | { type: 'link', href: string, value: string }>}
 */
export function linkifyNotes(text) {
    if (text == null || text === '') {
        return [];
    }

    const re = /https?:\/\/[^\s<>"{}|\\^`[\]]+/gi;
    const segments = [];
    let lastIndex = 0;
    let m = re.exec(text);

    while (m !== null) {
        if (m.index > lastIndex) {
            segments.push({ type: 'text', value: text.slice(lastIndex, m.index) });
        }
        const raw = m[0];
        const href = trimTrailingPunctuation(raw);
        if (isSafeHttpUrl(href)) {
            segments.push({ type: 'link', href, value: href });
        } else {
            segments.push({ type: 'text', value: raw });
        }
        lastIndex = m.index + raw.length;
        m = re.exec(text);
    }

    if (lastIndex < text.length) {
        segments.push({ type: 'text', value: text.slice(lastIndex) });
    }

    return segments;
}

function trimTrailingPunctuation(s) {
    return s.replace(/[.,;:!?)\]]+$/u, '');
}

function isSafeHttpUrl(href) {
    try {
        const u = new URL(href);

        return u.protocol === 'http:' || u.protocol === 'https:';
    } catch {
        return false;
    }
}

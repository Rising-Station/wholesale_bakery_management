/**
 * Human-readable date and time formatting utilities.
 */

/**
 * Format a date string (e.g. "2026-10-28" or ISO string) to "28 Oct 2026" or custom format.
 *
 * @param {string|Date|null} value
 * @param {Intl.DateTimeFormatOptions} [options]
 * @returns {string}
 */
export function formatDate(value, options = {}) {
    if (!value) return '-';

    // Pure YYYY-MM-DD string: parse directly to avoid timezone offset rolling back a day
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value.trim())) {
        const [year, month, day] = value.trim().split('-').map(Number);
        const d = new Date(year, month - 1, day);
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            ...options,
        });
    }

    const d = new Date(value);
    if (isNaN(d.getTime())) return String(value);

    return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...options,
    });
}

/**
 * Format a timestamp (e.g. ISO string) to "28 Oct 2026, 07:45 PM".
 *
 * @param {string|Date|null} value
 * @param {Intl.DateTimeFormatOptions} [options]
 * @returns {string}
 */
export function formatDateTime(value, options = {}) {
    if (!value) return '-';

    const d = new Date(value);
    if (isNaN(d.getTime())) return String(value);

    return d.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        ...options,
    });
}

export default {
    formatDate,
    formatDateTime,
};


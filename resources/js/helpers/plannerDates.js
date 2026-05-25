import { DateTime } from 'luxon';

const BERLIN_TIMEZONE = 'Europe/Berlin';

function dateTimeFromValue(value) {
    if (!value) return null;

    if (value instanceof Date) {
        return DateTime.fromJSDate(value).setZone(BERLIN_TIMEZONE);
    }

    if (typeof value === 'string') {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return DateTime.fromISO(value, { zone: BERLIN_TIMEZONE });
        }

        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)) {
            return DateTime.fromFormat(value, 'yyyy-MM-dd HH:mm:ss', { zone: BERLIN_TIMEZONE });
        }

        const iso = DateTime.fromISO(value, { setZone: true });
        if (iso.isValid) {
            return iso.setZone(BERLIN_TIMEZONE);
        }
    }

    if (typeof value?.toDate === 'function') {
        return DateTime.fromJSDate(value.toDate()).setZone(BERLIN_TIMEZONE);
    }

    const parsed = globalThis.moment?.(value);
    if (parsed?.isValid?.() && typeof parsed.toDate === 'function') {
        return DateTime.fromJSDate(parsed.toDate()).setZone(BERLIN_TIMEZONE);
    }

    return null;
}

export function serializePlannerDateToUtc(value, { endOfDay = false } = {}) {
    const dateTime = dateTimeFromValue(value);
    if (!dateTime) return value;

    const normalized = endOfDay
        ? dateTime.set({ hour: 23, minute: 59, second: 59, millisecond: 0 })
        : dateTime.startOf('day');

    return normalized
        .toUTC()
        .toISO({ suppressMilliseconds: false });
}

export function serializePlannerDateToBerlinDateString(value) {
    const dateTime = dateTimeFromValue(value);
    return dateTime ? dateTime.toFormat('yyyy-MM-dd') : value;
}

export function plannerValueToPickerDate(value) {
    const dateTime = dateTimeFromValue(value);
    return dateTime ? dateTime.set({ hour: 12, minute: 0, second: 0, millisecond: 0 }).toJSDate() : null;
}

export function formatPlannerDateForDisplay(value, format = 'dd.MM.yyyy') {
    const dateTime = dateTimeFromValue(value);
    return dateTime ? dateTime.toFormat(format) : '';
}

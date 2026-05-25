import { describe, expect, it } from 'vitest';
import {
    formatPlannerDateForDisplay,
    plannerValueToPickerDate,
    serializePlannerDateToBerlinDateString,
    serializePlannerDateToUtc,
} from '../../../resources/js/helpers/plannerDates';

describe('plannerDates', () => {
    it('serializes planner datetimes to utc iso strings', () => {
        expect(serializePlannerDateToUtc('2026-08-03T23:59:59+02:00', { endOfDay: true })).toBe('2026-08-03T21:59:59.000Z');
    });

    it('serializes planner dates to berlin calendar date strings', () => {
        expect(serializePlannerDateToBerlinDateString('2026-08-03T23:59:59+02:00')).toBe('2026-08-03');
    });

    it('formats stored utc instants as berlin display dates', () => {
        expect(formatPlannerDateForDisplay('2026-05-05T22:00:00.000Z')).toBe('06.05.2026');
        expect(formatPlannerDateForDisplay('2026-05-07T21:59:59.000Z')).toBe('07.05.2026');
    });

    it('converts stored utc instants to stable picker dates', () => {
        const pickerDate = plannerValueToPickerDate('2026-05-05T22:00:00.000Z');

        expect(pickerDate).toBeInstanceOf(Date);
        expect(formatPlannerDateForDisplay(pickerDate)).toBe('06.05.2026');
    });
});

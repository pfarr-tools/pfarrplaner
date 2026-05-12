import relativeDate from '@pfarr.tools/relative-date'

describe('relativeDate()', () => {
    it('returns "heute" for same day', () => expect(relativeDate('15.04.2024', '15.04.2024')).toBe('heute'))
    it('returns "gestern" for 1 day ago', () => expect(relativeDate('14.04.2024', '15.04.2024')).toBe('gestern'))
    it('returns "vorgestern" for 2 days ago', () => expect(relativeDate('13.04.2024', '15.04.2024')).toBe('vorgestern'))
    it('returns German weekday name for 3-6 days', () => {
        // 12.04.2024 = Freitag (Friday)
        expect(relativeDate('12.04.2024', '15.04.2024')).toBe('am Freitag')
    })
    it('returns "letzten Woche" phrase for exactly 7 days', () => {
        expect(relativeDate('08.04.2024', '15.04.2024')).toMatch(/letzten Woche/)
    })
    it('returns empty for empty date1', () => expect(relativeDate('', '15.04.2024')).toBe(''))
    it('returns empty for empty date2', () => expect(relativeDate('15.04.2024', '')).toBe(''))
})

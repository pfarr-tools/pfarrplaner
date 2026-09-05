import { romanize } from '@pfarr.tools/romanize'

describe('romanize()', () => {
    it.each([
        [1, 'I'], [4, 'IV'], [9, 'IX'], [14, 'XIV'],
        [40, 'XL'], [90, 'XC'], [400, 'CD'], [900, 'CM'],
        [1994, 'MCMXCIV'], [2024, 'MMXXIV'],
    ])('converts %i to %s', (input, expected) => {
        expect(romanize(input)).toBe(expected)
    })
    it('returns NaN for non-numeric input', () => expect(romanize('abc')).toBeNaN())
    it('handles numeric string', () => expect(romanize('5')).toBe('V'))
})

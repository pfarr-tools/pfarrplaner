import { slug } from '@pfarr.tools/slug'

describe('slug()', () => {
    it('lowercases and hyphenates', () => expect(slug('Hello World')).toBe('hello-world'))
    it('transliterates ä → ae', () => expect(slug('Käse')).toBe('kaese'))
    it('transliterates Ä → ae', () => expect(slug('Ärger')).toBe('aerger'))
    it('transliterates ö/Ö', () => expect(slug('Öl')).toBe('oel'))
    it('transliterates ü/Ü', () => expect(slug('Überfahrt')).toBe('ueberfahrt'))
    it('transliterates ß → ss', () => expect(slug('Straße')).toBe('strasse'))
    it('trims dashes', () => expect(slug('  hello  ')).toBe('hello'))
    it('collapses dashes', () => expect(slug('hello---world')).toBe('hello-world'))
    it('returns empty for null', () => expect(slug(null)).toBe(''))
    it('handles numbers', () => expect(slug(42)).toBe('42'))
    it('removes punctuation', () => expect(slug('Hello, World!')).toBe('hello-world'))
})

import { NameService } from '@/libraries/NameService.js'

describe('NameService — comma format ("Last, First")', () => {
    const ns = new NameService('Fischer, Christoph')
    it('parses first name', () => expect(ns.first).toBe('Christoph'))
    it('parses last name', () => expect(ns.last).toBe('Fischer'))
    it('builds full name', () => expect(ns.name).toBe('Christoph Fischer'))
    it('builds split array [last, first]', () => expect(ns.split).toEqual(['Fischer', 'Christoph']))
})

describe('NameService — space format ("First Last")', () => {
    it('parses single first + single last', () => {
        const ns = new NameService('Hans Müller')
        expect(ns.first).toBe('Hans')
        expect(ns.last).toBe('Müller')
    })
    it('treats all but last word as first name', () => {
        const ns = new NameService('Hans Peter Müller')
        expect(ns.first).toBe('Hans Peter')
        expect(ns.last).toBe('Müller')
    })
})

describe('NameService — spokenName override', () => {
    it('overrides first name', () => {
        expect(new NameService('Fischer, Christoph', 'Chris').first).toBe('Chris')
    })
    it('uses overridden name in .name', () => {
        expect(new NameService('Fischer, Christoph', 'Chris').name).toBe('Chris Fischer')
    })
    it('keeps last name unchanged', () => {
        expect(new NameService('Fischer, Christoph', 'Chris').last).toBe('Fischer')
    })
})

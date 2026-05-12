import { getTextSources } from '@/libraries/TextSources.js'

const makeService = (overrides = {}) => ({
    liturgicalInfo: {},
    baptisms: [],
    funerals: [],
    weddings: [],
    ...overrides,
})

describe('getTextSources()', () => {
    it('returns empty object for empty service', () => {
        expect(getTextSources(makeService())).toEqual({})
    })
    it('ignores liturgicalInfo keys if Bezeichnung not set', () => {
        const svc = makeService({ liturgicalInfo: { Predigt: { Bibelstelle: 'Joh 3,16' } } })
        expect(getTextSources(svc)).toEqual({})
    })
    it('adds Perikopen with romanized numeric keys', () => {
        const svc = makeService({
            liturgicalInfo: {
                Bezeichnung: 'Sonntag',
                Perikopen: {
                    1: { Bibelstelle: 'Gen 1,1' },
                    2: { Bibelstelle: 'Rev 22,1' },
                },
            },
        })
        const result = getTextSources(svc)
        expect(result['I']).toBe('Gen 1,1')
        expect(result['II']).toBe('Rev 22,1')
    })
    it('adds Perikopen with string keys unchanged', () => {
        const svc = makeService({
            liturgicalInfo: {
                Bezeichnung: 'Sonntag',
                Perikopen: { Psalm: { Bibelstelle: 'Ps 23' } },
            },
        })
        expect(getTextSources(svc)['Psalm']).toBe('Ps 23')
    })
    it('adds baptism text', () => {
        const svc = makeService({ baptisms: [{ text: 'Joh 3,16', candidate_name: 'Max' }] })
        expect(getTextSources(svc)['Taufspruch Max']).toBe('Joh 3,16')
    })
    it('skips baptism without text', () => {
        const svc = makeService({ baptisms: [{ text: '', candidate_name: 'Max' }] })
        expect(Object.keys(getTextSources(svc))).toHaveLength(0)
    })
    it('adds funeral burial text', () => {
        const svc = makeService({ funerals: [{ text: 'Ps 23', confirmation_text: null, wedding_text: null, buried_name: 'Müller' }] })
        expect(getTextSources(svc)['Beerdigungstext Müller']).toBe('Ps 23')
    })
    it('adds funeral confirmation text', () => {
        const svc = makeService({ funerals: [{ text: null, confirmation_text: 'Joh 11,25', wedding_text: null, buried_name: 'Müller' }] })
        expect(getTextSources(svc)['Denkspruch Müller']).toBe('Joh 11,25')
    })
    it('adds funeral wedding text', () => {
        const svc = makeService({ funerals: [{ text: null, confirmation_text: null, wedding_text: 'Koh 4,12', buried_name: 'Müller' }] })
        expect(getTextSources(svc)['Trauspruch Müller']).toBe('Koh 4,12')
    })
    it('adds wedding text', () => {
        const svc = makeService({ weddings: [{ text: 'Koh 4,12', spouse1_name: 'Anna', spouse2_name: 'Peter' }] })
        expect(getTextSources(svc)['Trauspruch Anna & Peter']).toBe('Koh 4,12')
    })
    it('skips wedding without text', () => {
        const svc = makeService({ weddings: [{ text: null, spouse1_name: 'A', spouse2_name: 'B' }] })
        expect(Object.keys(getTextSources(svc))).toHaveLength(0)
    })
})

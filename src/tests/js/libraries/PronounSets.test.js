import { ErPronounSet } from '@/libraries/PronounSets/ErPronounSet.js'
import { SiePronounSet } from '@/libraries/PronounSets/SiePronounSet.js'
import { PronounSetFactory } from '@/libraries/PronounSets/PronounSetFactory.js'

describe('PronounSetFactory', () => {
    it('returns ErPronounSet for "er"', () => expect(PronounSetFactory.get('er')).toBeInstanceOf(ErPronounSet))
    it('returns SiePronounSet for "sie"', () => expect(PronounSetFactory.get('sie')).toBeInstanceOf(SiePronounSet))
    it('defaults to ErPronounSet for unknown key', () => expect(PronounSetFactory.get('divers')).toBeInstanceOf(ErPronounSet))
})

describe('ErPronounSet', () => {
    const set = new ErPronounSet()

    it('has "er" pronoun', () => expect(set.data.er).toBe('er'))
    it('has possessive "sein"', () => expect(set.data.sein).toBe('sein'))
    it('has relational "der"', () => expect(set.data.der).toBe('der'))

    describe('replacementSet(prefix)', () => {
        const rs = set.replacementSet('p')

        it('maps lowercase key with prefix', () => expect(rs['p:er']).toBe('er'))
        it('adds capitalized variant for lowercase keys', () => expect(rs['p:Er']).toBe('Er'))
        it('maps capitalized key directly', () => expect(rs['p:Sohn']).toBe('Sohn'))
        it('does not double-add capitalized keys', () => {
            const keys = Object.keys(rs).filter(k => k === 'p:Sohn')
            expect(keys).toHaveLength(1)
        })
        it('capitalizes value for capitalized variant', () => {
            // 'er' → 'Er' (value capitalized too)
            expect(rs['p:Er']).toBe('Er')
        })
    })
})

describe('SiePronounSet', () => {
    const set = new SiePronounSet()

    it('maps "er" key to "sie"', () => expect(set.data.er).toBe('sie'))
    it('maps "sein" to "ihr"', () => expect(set.data.sein).toBe('ihr'))
    it('maps "seine" to "ihre"', () => expect(set.data.seine).toBe('ihre'))
    it('maps "der" to "die"', () => expect(set.data.der).toBe('die'))
    it('maps "Sohn" to "Tochter"', () => expect(set.data.Sohn).toBe('Tochter'))
    it('maps "Bruder" to "Schwester"', () => expect(set.data.Bruder).toBe('Schwester'))
    it('maps "Mann" to "Frau"', () => expect(set.data.Mann).toBe('Frau'))
    it('maps "Ehemann" to "Ehefrau"', () => expect(set.data.Ehemann).toBe('Ehefrau'))
})

import { uid } from '@/libraries/uid.js'

describe('uid()', () => {
    it('returns a string', () => expect(typeof uid()).toBe('string'))
    it('matches /^pf-\\d+$/', () => expect(uid()).toMatch(/^pf-\d+$/))
    it('returns unique values on consecutive calls', () => {
        expect(uid()).not.toBe(uid())
    })
    it('increments the counter on each call', () => {
        const a = parseInt(uid().split('-')[1])
        const b = parseInt(uid().split('-')[1])
        expect(b).toBe(a + 1)
    })
})

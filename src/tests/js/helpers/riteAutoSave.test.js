import { clearRiteDraft, getRiteDraft, saveRiteDraft } from '@/helpers/riteAutoSave'
import { beforeEach } from 'vitest'

describe('rite autosave drafts', () => {
    beforeEach(() => window.localStorage.clear())

    it('stores drafts and removes only the snapshot confirmed by the server', () => {
        const first = { id: 4, notes: 'first' }
        const snapshot = saveRiteDraft('funerals', 4, first)

        expect(getRiteDraft('funerals', 4).data).toEqual(first)
        saveRiteDraft('funerals', 4, { id: 4, notes: 'newer' })

        expect(clearRiteDraft('funerals', 4, snapshot)).toBe(false)
        expect(getRiteDraft('funerals', 4).data.notes).toBe('newer')
        expect(clearRiteDraft('funerals', 4, JSON.stringify({ id: 4, notes: 'newer' }))).toBe(true)
        expect(getRiteDraft('funerals', 4)).toBeNull()
    })

    it('preserves unrelated browser backups', () => {
        window.localStorage.setItem(
            'pfarrplaner',
            JSON.stringify({ funerals: { 4: { notes: 'backup' } } }),
        )
        const snapshot = saveRiteDraft('baptisms', 8, { notes: 'draft' })
        clearRiteDraft('baptisms', 8, snapshot)

        expect(JSON.parse(window.localStorage.getItem('pfarrplaner')).funerals[4].notes).toBe(
            'backup',
        )
    })
})

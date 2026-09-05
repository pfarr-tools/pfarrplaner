import AbsenceEditor from '@/Pages/Absences/AbsenceEditor.vue'
import PoolmasterEditor from '@/Pages/Admin/Poolmaster/Editor.vue'

describe('AbsenceEditor ISO submission', () => {
    it('stores ISO timestamps when the date range changes', () => {
        const ctx = {
            serializePlannerDate: AbsenceEditor.methods.serializePlannerDate,
            form: {
                from: null,
                to: null,
            },
        }

        AbsenceEditor.computed.dateRange.set.call(ctx, [
            new Date('2026-06-01T00:00:00+02:00'),
            new Date('2026-06-04T23:59:59+02:00'),
        ])

        expect(ctx.form.from).toBe('2026-05-31T22:00:00.000Z')
        expect(ctx.form.to).toBe('2026-06-04T21:59:59.000Z')
    })

    it('prepares replacement ranges as ISO timestamps', () => {
        const ctx = {
            maySelfAdminister: false,
            role: 'editor',
            serializePlannerDate: AbsenceEditor.methods.serializePlannerDate,
            form: {
                sick_days: false,
                reason: 'Urlaub',
                replacements: [
                    {
                        range: [
                            new Date('2026-06-01T00:00:00+02:00'),
                            new Date('2026-06-04T23:59:59+02:00'),
                        ],
                    },
                ],
            },
        }

        const prepared = AbsenceEditor.methods.prepareForm.call(ctx)

        expect(prepared.replacements[0].from).toBe('2026-05-31T22:00:00.000Z')
        expect(prepared.replacements[0].to).toBe('2026-06-04T21:59:59.000Z')
    })

    it('prepares main absence range as ISO timestamps even from naive local strings', () => {
        const ctx = {
            maySelfAdminister: false,
            role: 'editor',
            serializePlannerDate: AbsenceEditor.methods.serializePlannerDate,
            form: {
                from: '2026-08-02 00:00:00',
                to: '2026-08-03 23:59:59',
                sick_days: false,
                reason: 'Urlaub',
                replacements: [],
            },
        }

        const prepared = AbsenceEditor.methods.prepareForm.call(ctx)

        expect(prepared.from).toBe('2026-08-01T22:00:00.000Z')
        expect(prepared.to).toBe('2026-08-03T21:59:59.000Z')
    })

    it('treats self-administered absences as self-editor and allows deletion', () => {
        const ctx = {
            maySelfAdminister: true,
            mayApprove: false,
            mayCheck: false,
            form: {
                id: 7,
                workflow_status: 0,
            },
        }

        const mayEdit = AbsenceEditor.computed.mayEdit.call(ctx)
        const role = AbsenceEditor.computed.role.call({ ...ctx, mayEdit })
        const mayDelete = AbsenceEditor.computed.mayDelete.call({ ...ctx, mayEdit, role })

        expect(mayEdit).toBe(true)
        expect(role).toBe('self-editor')
        expect(mayDelete).toBe(true)
    })

    it('detects unsaved absences by missing id', () => {
        const unsaved = AbsenceEditor.computed.isPersisted.call({
            form: { id: null },
        })
        const saved = AbsenceEditor.computed.isPersisted.call({
            form: { id: 12 },
        })

        expect(unsaved).toBe(false)
        expect(saved).toBe(true)
    })

    it('adds a replacement row using the current absence range', () => {
        const ctx = {
            form: {
                replacements: [],
                from: '2026-08-01T22:00:00.000Z',
                to: '2026-08-03T21:59:59.000Z',
            },
        }

        AbsenceEditor.methods.addReplacement.call(ctx)

        expect(ctx.form.replacements).toHaveLength(1)
        expect(ctx.form.replacements[0].range).toEqual([
            '2026-08-01T22:00:00.000Z',
            '2026-08-03T21:59:59.000Z',
        ])
    })

    it('stores berlin calendar dates for poolmaster ranges', () => {
        const ctx = {
            myPoolmaster: {
                start: null,
                end: null,
            },
        }

        PoolmasterEditor.methods.setDateRange.call(ctx, [
            new Date('2026-08-22T00:00:00+02:00'),
            new Date('2026-08-31T23:59:59+02:00'),
        ])

        expect(ctx.myPoolmaster.start).toBe('2026-08-22')
        expect(ctx.myPoolmaster.end).toBe('2026-08-31')
    })
})

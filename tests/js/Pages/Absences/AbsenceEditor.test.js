import AbsenceEditor from '@/Pages/Absences/AbsenceEditor.vue'

describe('AbsenceEditor ISO submission', () => {
    it('stores ISO timestamps when the date range changes', () => {
        const ctx = {
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
})

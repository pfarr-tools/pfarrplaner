import Planner from '@/Pages/Absences/Planner.vue'

describe('Absences Planner timezone handling', () => {
    function makeContext() {
        const ctx = {}
        ctx.plannerDateTime = (value) => Planner.methods.plannerDateTime.call(ctx, value)
        ctx.formatPlannerDate = (value, format) => Planner.methods.formatPlannerDate.call(ctx, value, format)
        ctx.plannerIsoWeekday = (value) => Planner.methods.plannerIsoWeekday.call(ctx, value)
        return ctx
    }

    it('formats UTC timestamps as Berlin calendar dates', () => {
        const ctx = makeContext()

        expect(ctx.formatPlannerDate('2026-05-31T22:00:00.000000Z', 'dd.MM.yyyy')).toBe('01.06.2026')
        expect(ctx.formatPlannerDate('2026-05-31T22:00:00.000000Z', 'yyyy-MM')).toBe('2026-06')
        expect(ctx.plannerIsoWeekday('2026-05-31T22:00:00.000000Z')).toBe(1)
    })

    it('shows absence ranges with Berlin dates in tooltips', () => {
        const ctx = makeContext()

        const title = Planner.methods.absenceTitle.call(ctx, { canEdit: true }, {
            reason: 'Urlaub',
            from: '2026-05-31T22:00:00.000000Z',
            to: '2026-06-04T21:59:59.000000Z',
            workflow_status: 99,
            replacementText: null,
        })

        expect(title).toContain('(01.06.2026 - 04.06.2026)')
    })

    it('creates absences from Berlin calendar dates in the planner modal', () => {
        const post = vi.fn()
        const ctx = {
            year: 2026,
            month: 5,
            $inertia: { post },
            draftAbsenceRange: () => [
                new Date('2026-05-05T22:00:00.000Z'),
                new Date('2026-05-06T22:00:00.000Z'),
            ],
            closeCreateModals: vi.fn(),
            createAbsencePayload: Planner.methods.createAbsencePayload,
            submitNewAbsence: Planner.methods.submitNewAbsence,
        }

        Planner.methods.createAbsenceForRange.call(ctx, { id: 30 })

        expect(ctx.closeCreateModals).toHaveBeenCalled()
        expect(post).toHaveBeenCalledWith('/absence.store', {
            user_id: 30,
            reason: 'Urlaub',
            from: '2026-05-05T22:00:00.000Z',
            to: '2026-05-07T21:59:59.000Z',
        })
    })
})

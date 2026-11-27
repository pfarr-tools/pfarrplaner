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

    it('uses a dedicated warning class for unapproved absences', () => {
        const ctx = {
            userDays: {
                30: {
                    1: {
                        absence: { workflow_status: 10 },
                    },
                },
            },
        }

        expect(Planner.methods.absenceStatusClass.call(ctx, ctx.userDays[30][1].absence))
            .toContain('absence-status-unapproved')
    })

    it('groups pastor and employee visibility controls separately', () => {
        const ctx = {
            toggleableUsers: [
                { id: 1, isPastor: true },
                { id: 2, isPastor: false },
            ],
            plannerVisibilityUsers: [
                { id: 1, isPastor: true },
                { id: 2, isPastor: false },
            ],
            $page: { props: { labels: { pastor: 'Pfarrer' } } },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        const sections = Planner.computed.plannerVisibilitySections.call(ctx)

        expect(sections).toEqual([
            { label: 'Pfarrer:innen', users: [{ id: 1, isPastor: true }] },
            { label: 'Mitarbeitende', users: [{ id: 2, isPastor: false }] },
        ])
    })

    it('groups pastor visibility controls by pool without duplicating multi-pool pastors', () => {
        const northPool = { id: 10, name: 'Nord', groupable: true }
        const southPool = { id: 11, name: 'Süd', groupable: true }
        const ctx = {
            toggleableUsers: [
                { id: 1, isPastor: true, pools: [northPool, southPool] },
                { id: 2, isPastor: true, pools: [] },
                { id: 3, isPastor: false },
            ],
            plannerVisibilityUsers: [
                { id: 1, isPastor: true, pools: [northPool, southPool] },
                { id: 2, isPastor: true, pools: [] },
                { id: 3, isPastor: false },
            ],
            $page: { props: { labels: { pastor: 'Pfarrer' } } },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        const sections = Planner.computed.plannerVisibilitySections.call(ctx)

        expect(sections).toEqual([
            {
                label: 'Pfarrer:innen Nord',
                users: [{ id: 1, isPastor: true, pools: [northPool, southPool] }],
            },
            { label: 'Pfarrer:innen', users: [{ id: 2, isPastor: true, pools: [] }] },
            { label: 'Mitarbeitende', users: [{ id: 3, isPastor: false }] },
        ])
    })

    it('does not add pastors to the visibility controls twice', () => {
        const pastor = { id: 1, isPastor: true, show_absences_with_services: false, sortName: 'Pastor' }
        const ctx = {
            isPastor: true,
            pinnedPastors: null,
            pinnedUsers: [],
            sections: [],
            toggleableUsers: [],
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: true } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        Planner.methods.sortUsers.call(ctx, [pastor])

        expect(ctx.toggleableUsers.map(user => user.id)).toEqual([1])
    })

    it('groups visible pastors into every matching group and keeps ungrouped pastors visible', () => {
        const northPool = { id: 10, name: 'Nord', groupable: true }
        const southPool = { id: 11, name: 'Süd', groupable: true }
        const regularPool = { id: 20, name: 'Sonstige', groupable: false }
        const pastors = [
            {
                id: 1,
                isPastor: true,
                show_absences_with_services: true,
                pools: [northPool, southPool],
                sortName: 'A',
            },
            {
                id: 2,
                isPastor: true,
                show_absences_with_services: true,
                pools: [regularPool],
                sortName: 'B',
            },
        ]
        const ctx = {
            isPastor: true,
            pinnedPastors: null,
            pinnedUsers: [],
            sections: [],
            toggleableUsers: [],
            openSections: {},
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: true } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        const sorted = Planner.methods.sortUsers.call(ctx, pastors)

        expect(ctx.sections).toEqual([
            'Pfarrer:innen Nord',
            'Pfarrer:innen Süd',
            'Pfarrer:innen',
        ])
        expect(sorted['Pfarrer:innen Nord'].map((user) => user.id)).toEqual([1])
        expect(sorted['Pfarrer:innen Süd'].map((user) => user.id)).toEqual([1])
        expect(sorted['Pfarrer:innen'].map((user) => user.id)).toEqual([2])
        expect(ctx.openSections['Pfarrer:innen Nord']).toBe(true)
    })

    it('shows grouped pastors and visibility controls for non-pastors', () => {
        const pool = { id: 10, name: 'Oberes Gäu', groupable: true }
        const pastor = {
            id: 1,
            isPastor: true,
            show_absences_with_services: true,
            pools: [pool],
            sortName: 'A',
        }
        const ctx = {
            isPastor: false,
            pinnedPastors: null,
            pinnedUsers: [],
            sections: [],
            toggleableUsers: [],
            openSections: {},
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: false } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        const sorted = Planner.methods.sortUsers.call(ctx, [pastor])

        expect(sorted['Pfarrer:innen Oberes Gäu'].map((user) => user.id)).toEqual([1])
        expect(ctx.sections).toEqual(['Pfarrer:innen Oberes Gäu'])
        expect(ctx.toggleableUsers.map((user) => user.id)).toEqual([1])
    })

    it('does not add a deactivated pastor to the employee visibility controls', () => {
        const pastor = {
            id: 1,
            isPastor: true,
            show_absences_with_services: false,
            pools: [{ id: 10, name: 'Oberes Gäu', groupable: true }],
            sortName: 'A',
        }
        const ctx = {
            isPastor: false,
            pinnedPastors: [],
            pinnedUsers: [],
            sections: [],
            toggleableUsers: [],
            openSections: {},
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: false } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        const sorted = Planner.methods.sortUsers.call(ctx, [pastor])

        expect(sorted).toEqual({})
        expect(ctx.toggleableUsers.map((user) => user.id)).toEqual([1])
    })

    it('does not add an empty general pastor section', () => {
        const groupablePool = { id: 10, name: 'Nord', groupable: true }
        const pastor = {
            id: 1,
            isPastor: true,
            show_absences_with_services: true,
            pools: [groupablePool],
            sortName: 'A',
        }
        const ctx = {
            isPastor: true,
            pinnedPastors: null,
            pinnedUsers: [],
            sections: [],
            toggleableUsers: [],
            openSections: {},
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: true } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
        }

        const sorted = Planner.methods.sortUsers.call(ctx, [pastor])

        expect(ctx.sections).toEqual(['Pfarrer:innen Nord'])
        expect(sorted['Pfarrer:innen']).toBeUndefined()
    })

    it('allows hiding employees and omits an empty employee section', () => {
        const employee = {
            id: 2,
            isPastor: false,
            show_absences_with_services: false,
            sortName: 'B',
        }
        const ctx = {
            isPastor: true,
            pinnedPastors: null,
            pinnedUsers: [2],
            plannerUsers: [employee],
            sections: [],
            users: {},
            toggleableUsers: [],
            openSections: {},
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: true } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
            sortUsers: Planner.methods.sortUsers,
            persistPlannerSetting: vi.fn(),
        }

        const sorted = Planner.methods.sortUsers.call(ctx, [employee])

        expect(sorted['Mitarbeitende'].map((user) => user.id)).toEqual([2])
        expect(ctx.sections).toEqual(['Mitarbeitende'])
        expect(ctx.toggleableUsers.map((user) => user.id)).toEqual([2])

        ctx.users = sorted
        Planner.methods.togglePinned.call(ctx, employee)

        expect(ctx.sections).toEqual([])
        expect(ctx.users['Mitarbeitende']).toBeUndefined()
    })

    it('updates pastor group sections when visibility changes', () => {
        const pastor = {
            id: 1,
            isPastor: true,
            show_absences_with_services: true,
            pools: [{ id: 10, name: 'Nord', groupable: true }],
            sortName: 'A',
        }
        const ctx = {
            isPastor: true,
            pinnedPastors: [],
            pinnedUsers: [],
            plannerUsers: [pastor],
            sections: [],
            users: {},
            toggleableUsers: [],
            openSections: {},
            $page: {
                props: {
                    currentUser: { data: { id: 99, isPastor: true } },
                    labels: { pastor: 'Pfarrer' },
                },
            },
            pastorPluralLabel: () => 'Pfarrer:innen',
            sortUsers: Planner.methods.sortUsers,
            persistPlannerSetting: vi.fn(),
        }

        ctx.users = Planner.methods.sortUsers.call(ctx, ctx.plannerUsers)
        Planner.methods.togglePinned.call(ctx, pastor)

        expect(ctx.sections).toEqual(['Pfarrer:innen Nord'])

        Planner.methods.togglePinned.call(ctx, pastor)

        expect(ctx.sections).toEqual([])
    })
})

import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import CalendarPage from '@/Pages/Calendar/Calendar.vue'

const calendarApi = {
    get: vi.fn(() => Promise.resolve({ data: {} })),
}

function mountCalendarPage(props = {}) {
    return mount(CalendarPage, {
        props: {
            date: '2024-01',
            cities: [{ id: 1, name: 'Musterstadt', childIds: [], is_org: false }],
            years: [2024],
            canCreate: true,
            ministries: [],
            writableCities: [],
            calendars: [{ id: 'events-1', name: 'Kalender 1' }],
            initialCalendarData: { loadedDate: '2024-01', data: {} },
            ...props,
        },
        global: {
            stubs: {
                Modal: { template: '<div><slot /></div>' },
                PeopleSelect: true,
                FormSelectize: true,
                FormCheck: true,
                CalendarNavTop: true,
                CalendarNavControlSidebar: true,
                CalendarPaneVertical: true,
                CalendarPaneHorizontal: true,
                CalendarPaneMobile: true,
                EventsCalendar: true,
            },
            mocks: {
                $page: {
                    props: {
                        currentUser: { data: { id: 1, isPastor: false } },
                        settings: {},
                        labels: {
                            pastor: 'Pfarrer:in',
                            organist: 'Organist:in',
                            sacristan: 'Mesner:in',
                        },
                    },
                },
                $api: () => calendarApi,
            },
        },
    })
}

describe('Calendar page target mode loading', () => {
    it('does not eagerly load people and ministries on mount', () => {
        mountCalendarPage()
        expect(calendarApi.get).not.toHaveBeenCalled()
    })

    it('loads helper data only when target mode is opened', async () => {
        calendarApi.get
            .mockResolvedValueOnce({ data: { users: [{ id: 1, name: 'Test User' }], teams: [] } })
            .mockResolvedValueOnce({ data: [{ category: 'Lektor:in' }] })

        const wrapper = mountCalendarPage()
        wrapper.vm.toggleTargetMode(true)
        await Promise.resolve()
        await Promise.resolve()

        expect(calendarApi.get).toHaveBeenNthCalledWith(1, '/api.people.select')
        expect(calendarApi.get).toHaveBeenNthCalledWith(2, '/api.ministries.list')
        expect(wrapper.vm.showTargetModeModal).toBe(true)
    })
})

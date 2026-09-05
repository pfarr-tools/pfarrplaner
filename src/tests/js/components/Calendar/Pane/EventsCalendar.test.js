import { mount, flushPromises } from '@vue/test-utils'
import { vi } from 'vitest'
import EventsCalendar from '@/components/Calendar/Pane/EventsCalendar.vue'

function makeEvent(overrides = {}) {
    return {
        id: 'event-1',
        title: 'Mehrtägiger Termin',
        body: '',
        location: 'Gemeindehaus',
        start: '2024-05-13 00:00:00',
        end: '2024-05-15 23:59:59',
        isReadOnly: false,
        isAllday: true,
        raw: {
            occurence_id: 1,
            isRecurring: false,
            event_id: 1,
            event_slug: 'mehrtaegiger-termin',
            event_class: 'event',
        },
        ...overrides,
    }
}

function mountEventsCalendar(events = []) {
    const apiMock = {
        get: vi.fn(() => Promise.resolve({ data: { data: events } })),
        delete: vi.fn(() => Promise.resolve({})),
    }

    return mount(EventsCalendar, {
        props: {
            date: '2024-05',
            calendar: ['events-1'],
            writableCities: [],
        },
        global: {
            stubs: {
                Modal: true,
            },
            mocks: {
                $api: () => apiMock,
            },
        },
    })
}

describe('EventsCalendar', () => {
    it('renders one spanning week entry for all-day multi-day events', async () => {
        const wrapper = mountEventsCalendar([makeEvent()])

        await flushPromises()

        const targetWeek = wrapper.vm.weeks.find(week => week.key === '2024-05-13')
        const spanRows = wrapper.vm.weekSpanRows(targetWeek)

        expect(spanRows).toHaveLength(1)
        expect(spanRows[0]).toHaveLength(1)
        expect(spanRows[0][0].segment.startColumn).toBe(1)
        expect(spanRows[0][0].segment.columnSpan).toBe(3)
        expect(wrapper.vm.visibleEvents('2024-05-13')).toHaveLength(0)
        expect(wrapper.vm.visibleEvents('2024-05-14')).toHaveLength(0)
        expect(wrapper.vm.visibleEvents('2024-05-15')).toHaveLength(0)
        expect(wrapper.findAll('.event-span-card')).toHaveLength(1)
        expect(wrapper.find('.event-span-card').text()).toContain('13.05. - 15.05.')
    })

    it('splits a timed multi-day event across week boundaries into one span per affected week', async () => {
        const wrapper = mountEventsCalendar([
            makeEvent({
                id: 'event-2',
                title: 'Freizeit',
                start: '2024-05-18 18:00:00',
                end: '2024-05-21 09:00:00',
                isAllday: false,
            }),
        ])

        await flushPromises()

        const firstWeek = wrapper.vm.weeks.find(week => week.key === '2024-05-13')
        const secondWeek = wrapper.vm.weeks.find(week => week.key === '2024-05-20')
        const firstWeekSpan = wrapper.vm.weekSpanRows(firstWeek)[0][0]
        const secondWeekSpan = wrapper.vm.weekSpanRows(secondWeek)[0][0]

        expect(firstWeekSpan.segment.startColumn).toBe(6)
        expect(firstWeekSpan.segment.columnSpan).toBe(2)
        expect(firstWeekSpan.segment.isFirstDay).toBe(true)
        expect(firstWeekSpan.segment.isLastDay).toBe(false)
        expect(secondWeekSpan.segment.startColumn).toBe(1)
        expect(secondWeekSpan.segment.columnSpan).toBe(2)
        expect(secondWeekSpan.segment.isFirstDay).toBe(false)
        expect(secondWeekSpan.segment.isLastDay).toBe(true)
        expect(wrapper.findAll('.event-span-card')).toHaveLength(2)
    })

    it('keeps single-day events inside their day cell', async () => {
        const wrapper = mountEventsCalendar([
            makeEvent({
                id: 'event-3',
                title: 'Sitzung',
                start: '2024-05-22 19:30:00',
                end: '2024-05-22 21:00:00',
                isAllday: false,
            }),
        ])

        await flushPromises()

        expect(wrapper.vm.visibleEvents('2024-05-22')).toHaveLength(1)
        expect(wrapper.findAll('.event-span-card')).toHaveLength(0)
        expect(wrapper.findAll('.day-events .event-card')).toHaveLength(1)
    })

    it('shows start and end date/time on a timed multi-day span within one week', async () => {
        const wrapper = mountEventsCalendar([
            makeEvent({
                id: 'event-4',
                title: 'Probe',
                start: '2024-05-14 18:00:00',
                end: '2024-05-16 09:00:00',
                isAllday: false,
            }),
        ])

        await flushPromises()

        expect(wrapper.findAll('.event-span-card')).toHaveLength(1)
        expect(wrapper.find('.event-span-card').text()).toContain('14.05. 18:00 -> 16.05. 09:00')
    })

    it('applies a visible default background to all-day events when the source color is white', async () => {
        const wrapper = mountEventsCalendar([makeEvent()])
        const allDayEvent = makeEvent({
            customStyle: {
                backgroundColor: 'white',
                borderColor: '',
                color: 'black',
            },
        })

        await flushPromises()

        expect(wrapper.vm.eventStyle(allDayEvent)).toMatchObject({
            '--event-bg': '#fff3cd',
            '--event-border': '#f0d68f',
        })
    })
})

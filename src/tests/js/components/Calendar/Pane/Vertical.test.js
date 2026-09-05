import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import VerticalPane from '@/components/Calendar/Pane/Vertical.vue'

function mountVerticalPane(props = {}, apiMock = { get: vi.fn() }) {
    return mount(VerticalPane, {
        props: {
            date: '2024-01',
            cities: [{ id: 1, name: 'Musterstadt', is_org: false, childIds: [] }],
            canCreate: true,
            targetMode: false,
            target: null,
            initialData: {
                loadedDate: '2024-01',
                data: {
                    '2024-01-14': {
                        date: '2024-01-14',
                        liturgy: {},
                        absences: [],
                        services: { 1: [{ id: 7, titleText: 'Gottesdienst' }] },
                    },
                },
            },
            ...props,
        },
        global: {
            stubs: {
                CalendarDayHeader: true,
                CalendarCell: true,
                NavButton: true,
            },
            mocks: {
                $api: () => apiMock,
            },
        },
    })
}

describe('Calendar vertical pane', () => {
    it('uses initial calendar data without loading again on mount', () => {
        const apiMock = { get: vi.fn() }
        const wrapper = mountVerticalPane({}, apiMock)

        expect(apiMock.get).not.toHaveBeenCalled()
        expect(wrapper.vm.getServices({ id: 1, is_org: false }, '2024-01-14')).toHaveLength(1)
    })

    it('loads data from the month API when no initial data is present', async () => {
        const apiMock = {
            get: vi.fn(() => Promise.resolve({
                data: {
                    loadedDate: '2024-02',
                    data: {
                        '2024-02-04': {
                            date: '2024-02-04',
                            liturgy: {},
                            absences: [],
                            services: { 1: [{ id: 11, titleText: 'Abendgottesdienst' }] },
                        },
                    },
                },
            })),
        }

        const wrapper = mountVerticalPane({
            date: '2024-02',
            initialData: null,
        }, apiMock)

        await Promise.resolve()
        await Promise.resolve()

        expect(window.route).toHaveBeenCalledWith('api.calendar.month', {
            date: '2024-02',
        })
        expect(apiMock.get).toHaveBeenCalledWith('/api.calendar.month')
        expect(wrapper.vm.loadedDate).toBe('2024-02')
        expect(wrapper.vm.getServices({ id: 1, is_org: false }, '2024-02-04')).toHaveLength(1)
    })
})

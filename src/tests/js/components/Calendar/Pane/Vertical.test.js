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
        wrapper.unmount()
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
        wrapper.unmount()
    })

    it('reloads the current month after a browser realtime event', async () => {
        const apiMock = {
            get: vi.fn(() => Promise.resolve({
                data: {
                    loadedDate: '2024-01',
                    data: {
                        '2024-01-20': {
                            date: '2024-01-20',
                            liturgy: {},
                            absences: [],
                            services: { 1: [{ id: 7, titleText: 'Aktualisierter Gottesdienst' }] },
                        },
                    },
                },
            })),
        }
        const wrapper = mountVerticalPane({}, apiMock)

        window.dispatchEvent(new CustomEvent('pfarrplaner:service-changed', {
            detail: { change: 'updated', serviceId: 7, date: '2024-01-14' },
        }))
        await Promise.resolve()
        await Promise.resolve()

        expect(apiMock.get).toHaveBeenCalledWith('/api.calendar.month')
        expect(wrapper.vm.getServices({ id: 1, is_org: false }, '2024-01-14')).toHaveLength(0)
        expect(wrapper.vm.getServices({ id: 1, is_org: false }, '2024-01-20')[0].titleText).toBe(
            'Aktualisierter Gottesdienst',
        )
        wrapper.unmount()
    })

    it('ignores realtime events outside the displayed month', async () => {
        const apiMock = { get: vi.fn() }
        const wrapper = mountVerticalPane({}, apiMock)

        window.dispatchEvent(new CustomEvent('pfarrplaner:service-changed', {
            detail: { change: 'updated', serviceId: 7, date: '2024-02-20', oldDate: '2024-02-20' },
        }))
        await Promise.resolve()

        expect(apiMock.get).not.toHaveBeenCalled()
        wrapper.unmount()
    })

    it('reloads when a service moves into or out of the displayed month', async () => {
        const apiMock = {
            get: vi.fn(() => Promise.resolve({
                data: { loadedDate: '2024-01', data: {} },
            })),
        }
        const wrapper = mountVerticalPane({}, apiMock)

        window.dispatchEvent(new CustomEvent('pfarrplaner:service-changed', {
            detail: { change: 'updated', serviceId: 7, date: '2024-02-20', oldDate: '2024-01-20' },
        }))
        await Promise.resolve()
        await Promise.resolve()

        expect(apiMock.get).toHaveBeenCalledWith('/api.calendar.month')
        wrapper.unmount()
    })

    it('preserves the scroll position when reloading the current month', async () => {
        const apiMock = {
            get: vi.fn(() => Promise.resolve({
                data: {
                    loadedDate: '2024-01',
                    data: {},
                },
            })),
        }
        const wrapper = mountVerticalPane({}, apiMock)
        const scrollContainer = wrapper.find('.calendar-grid-wrapper').element
        scrollContainer.scrollTop = 321
        scrollContainer.scrollLeft = 17

        window.dispatchEvent(new CustomEvent('pfarrplaner:service-changed', {
            detail: { change: 'updated', serviceId: 7, date: '2024-01-14' },
        }))
        await Promise.resolve()
        await Promise.resolve()
        await wrapper.vm.$nextTick()

        expect(scrollContainer.scrollTop).toBe(321)
        expect(scrollContainer.scrollLeft).toBe(17)
        wrapper.unmount()
    })

    it('reports a failed realtime refresh without reloading the page', async () => {
        const error = new Error('refresh failed')
        const apiMock = { get: vi.fn(() => Promise.reject(error)) }
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
        const wrapper = mountVerticalPane({}, apiMock)

        window.dispatchEvent(new CustomEvent('pfarrplaner:service-changed', {
            detail: { change: 'updated', serviceId: 7, date: '2024-01-14' },
        }))
        await Promise.resolve()
        await Promise.resolve()

        expect(consoleError).toHaveBeenCalledWith(
            '[Pfarrplaner] Calendar month refresh failed',
            {
                date: '2024-01',
                error,
                status: undefined,
                url: undefined,
                responseData: undefined,
            },
        )
        consoleError.mockRestore()
        wrapper.unmount()
    })

})

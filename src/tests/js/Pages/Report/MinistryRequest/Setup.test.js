import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import Setup from '@/Pages/Report/MinistryRequest/Setup.vue'

const cities = [
    { id: 1, name: 'Gemeinde A' },
    { id: 2, name: 'Gemeinde B' },
]

const users = [
    { id: 2, name: 'Empfänger', email: 'empfaenger@example.test' },
]

const stubs = {
    AdminLayout: { template: '<div><slot name="navbar-left" /><slot /></div>' },
    FormSelectize: { template: '<div />' },
    LocationSelect: { template: '<div />' },
    FormDateRangePicker: { template: '<div />' },
    PeopleSelect: { template: '<div />' },
    FakeTable: { template: '<div><slot /></div>' },
    NavButton: { template: '<button><slot /></button>' },
}

function mountSetup() {
    const post = vi.fn(() => Promise.resolve({ data: [] }))
    const inertiaPost = vi.fn()
    const wrapper = mount(Setup, {
        props: {
            cities,
            ministries: { P: 'Pfarrer/in' },
            locations: [],
            users,
            teams: {},
        },
        global: {
            stubs,
            mocks: {
                $api: () => ({ post }),
                $inertia: { post: inertiaPost },
            },
        },
    })

    return { wrapper, post, inertiaPost }
}

describe('Ministry request setup', () => {
    it('loads services for all selected churches', async () => {
        const { wrapper, post } = mountSetup()

        wrapper.vm.myCities = [1, 2]
        await wrapper.vm.getServices()

        const request = window.route.mock.calls
            .map(call => call[1])
            .filter(params => params?.step === 'services' && params?.cities)
            .at(-1)
        expect(request.cities).toEqual([1, 2])
        expect(request.city).toBeUndefined()
    })

    it('includes the selected recipient email address when sending', () => {
        const { wrapper, inertiaPost } = mountSetup()
        wrapper.vm.recipients = [{ id: 2, name: 'Empfänger' }]
        wrapper.vm.services = [{ id: 10, checked: true }]
        wrapper.vm.myMinistry = 'P'

        wrapper.vm.sendMessage()

        expect(inertiaPost).toHaveBeenCalledWith('/reports.render', expect.objectContaining({
            recipients: [2],
            address: { 2: 'empfaenger@example.test' },
        }))
    })

    it('passes the selected ministry and open-only filter when loading services', async () => {
        const { wrapper } = mountSetup()
        wrapper.vm.myMinistry = 'P'
        wrapper.vm.onlyOpenServices = true

        await wrapper.vm.getServices()

        const request = window.route.mock.calls
            .map(call => call[1])
            .filter(params => params?.step === 'services' && params?.cities)
            .at(-1)
        expect(request.ministry).toBe('P')
        expect(request.only_open).toBe(true)
    })
})

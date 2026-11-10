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

function mountSetup({ users: setupUsers = users, postResponse } = {}) {
    const post = vi.fn((url) =>
        Promise.resolve({
            data: url === '/api.people.email' ? postResponse || { person: null } : [],
        }),
    )
    const inertiaPost = vi.fn()
    const wrapper = mount(Setup, {
        props: {
            cities,
            ministries: { P: 'Pfarrer/in' },
            locations: [],
            users: setupUsers,
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

    it('includes the selected recipient email address when sending', async () => {
        const { wrapper, inertiaPost } = mountSetup()
        wrapper.vm.recipients = [{ id: 2, name: 'Empfänger' }]
        wrapper.vm.services = [{ id: 10, checked: true }]
        wrapper.vm.myMinistry = 'P'

        await wrapper.vm.sendMessage()

        expect(inertiaPost).toHaveBeenCalledWith('/reports.render', expect.objectContaining({
            recipients: [2],
            address: { 2: 'empfaenger@example.test' },
        }))
    })

    it('uses the existing person when an entered address is already assigned', async () => {
        const existingPerson = { id: 3, name: 'Vorhandene Person', email: 'vorhanden@example.test' }
        const { wrapper, inertiaPost } = mountSetup({
            users: [{ id: 2, name: 'Empfänger', email: '' }],
            postResponse: { person: existingPerson },
        })
        window.prompt = vi.fn(() => existingPerson.email)
        window.confirm.mockReturnValue(true)
        wrapper.vm.recipients = [{ id: 2, name: 'Empfänger' }]
        wrapper.vm.services = [{ id: 10, checked: true }]
        wrapper.vm.myMinistry = 'P'

        await wrapper.vm.sendMessage()

        expect(inertiaPost).toHaveBeenCalledWith('/reports.render', expect.objectContaining({
            recipients: [existingPerson.id],
            address: { [existingPerson.id]: existingPerson.email },
        }))
    })

    it('asks for another address when a duplicate is rejected', async () => {
        const { wrapper, post, inertiaPost } = mountSetup({
            users: [{ id: 2, name: 'Empfänger', email: '' }],
            postResponse: { person: { id: 3, name: 'Vorhandene Person', email: 'vorhanden@example.test' } },
        })
        let emailChecks = 0
        post.mockImplementation((url) =>
            Promise.resolve({
                data:
                    url === '/api.people.email'
                        ? { person: emailChecks++ === 0 ? { id: 3, name: 'Vorhandene Person', email: 'vorhanden@example.test' } : null }
                        : [],
            }),
        )
        window.prompt = vi
            .fn()
            .mockReturnValueOnce('vorhanden@example.test')
            .mockReturnValueOnce('neu@example.test')
        window.confirm.mockReturnValue(false)
        wrapper.vm.recipients = [{ id: 2, name: 'Empfänger' }]
        wrapper.vm.services = [{ id: 10, checked: true }]
        wrapper.vm.myMinistry = 'P'

        await wrapper.vm.sendMessage()

        expect(inertiaPost).toHaveBeenCalledWith('/reports.render', expect.objectContaining({
            recipients: [2],
            address: { 2: 'neu@example.test' },
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

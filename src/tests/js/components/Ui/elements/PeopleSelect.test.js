import { flushPromises, shallowMount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { vi } from 'vitest'
import PeopleSelect from '@/components/Ui/elements/PeopleSelect.vue'

const currentUser = { id: 1, name: 'Test User', api_token: 'test-token' }

const mountPeopleSelect = (apiResponse) =>
    shallowMount(PeopleSelect, {
        props: {
            people: [currentUser],
            city: { id: 10, name: 'Teststadt' },
        },
        global: {
            mocks: {
                $page: { props: { currentUser: { data: currentUser } } },
                $api: () => ({ post: vi.fn(() => Promise.resolve({ data: apiResponse })) }),
            },
        },
    })

describe('PeopleSelect duplicate email handling', () => {
    it('shows the existing person before creating a duplicate email', async () => {
        const person = { id: 2, first_name: 'Anna', last_name: 'Müller', email: 'anna@example.test' }
        const wrapper = mountPeopleSelect({ person })
        wrapper.vm.newPerson.email = person.email
        wrapper.vm.showNewPersonModal = true

        await wrapper.vm.closeNewPersonModal()
        await flushPromises()
        await nextTick()

        expect(wrapper.vm.duplicatePerson).toEqual(person)
        expect(wrapper.vm.showNewPersonModal).toBe(true)
    })

    it('allows continuing with another email address', async () => {
        const wrapper = mountPeopleSelect({ person: { id: 2, name: 'Anna Müller' } })
        wrapper.vm.duplicatePerson = { id: 2, name: 'Anna Müller' }

        wrapper.vm.useAnotherEmail()
        await flushPromises()

        expect(wrapper.vm.duplicatePerson).toBeNull()
    })
})

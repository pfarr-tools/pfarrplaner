import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import PredicantServicesTab from '@/components/HomeScreen/PredicantServicesTab.vue'

describe('PredicantServicesTab', () => {
    it('filters services by the selected city and persists the selection', async () => {
        const post = vi.fn(() => Promise.resolve())
        const wrapper = mount(PredicantServicesTab, {
            props: {
                title: 'Offene Gottesdienste',
                services: [
                    { id: 1, city_id: 1, titleText: 'Stadt A' },
                    { id: 2, city_id: 2, titleText: 'Stadt B' },
                ],
                cities: [{ id: 1, name: 'Stadt A' }, { id: 2, name: 'Stadt B' }],
                settings: { predicantServicesCities: [1] },
                user: { id: 1 },
            },
            global: {
                mocks: {
                    $api: () => ({ post }),
                    $settings: {},
                },
                stubs: { 'form-selectize': true, 'basic-info': true, 'details-info': true, 'fake-table': true },
            },
        })

        expect(wrapper.vm.filteredServices).toHaveLength(1)
        expect(wrapper.vm.filteredServices[0].id).toBe(1)

        await wrapper.vm.updateCities([2])
        expect(wrapper.vm.filteredServices[0].id).toBe(2)
        expect(post).toHaveBeenCalledWith(expect.anything(), { value: [2] })
    })
})

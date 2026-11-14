import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import Offerings from '@/Pages/Public/City/Offerings.vue'

const city = { id: 1, name: 'Musterstadt' }
const service = {
    id: 10,
    date: '2026-11-14',
    time: '10:00',
    title: 'Gottesdienst',
    location: 'Stadtkirche',
    offering_goal: 'Brot für die Welt',
    offering_amount: '',
    offering_denominations: {},
    offerings_counter1: '',
    offerings_counter2: '',
}
const denominations = {
    10000: '100 €-Scheine',
    5000: '50 €-Scheine',
    2000: '20 €-Scheine',
    1000: '10 €-Scheine',
    500: '5 €-Scheine',
    200: '2 €-Münzen',
    100: '1 €-Münzen',
    50: '50 Ct.-Münzen',
    20: '20 Ct.-Münzen',
    10: '10 Ct.-Münzen',
    5: '5 Ct.-Münzen',
    2: '2 Ct.-Münzen',
    1: '1 Ct.-Münzen',
}

describe('Public offerings page', () => {
    const mountPage = (props = {}, inertia = { post: vi.fn() }, flash = {}) =>
        mount(Offerings, {
            props: {
                city,
                services: [service],
                fallback: false,
                denominations,
                ...props,
            },
            global: {
                mocks: {
                    route: vi.fn((name) => `/${name}`),
                    $page: { props: { flash } },
                    $inertia: inertia,
                },
            },
        })

    it('keeps the denomination editor collapsed until requested', async () => {
        const wrapper = mountPage()

        expect(wrapper.find('[data-testid="service-selection-view"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="offering-entry-view"]').exists()).toBe(false)
        await wrapper.get('[data-testid="service-selector"]').trigger('click')

        expect(wrapper.get('#offerings-counter1').exists()).toBe(true)
        expect(wrapper.get('#offerings-counter2').exists()).toBe(true)
        expect(wrapper.find('[data-testid="denomination-editor"]').exists()).toBe(false)
        await wrapper.get('[data-testid="toggle-denominations"]').trigger('click')
        expect(wrapper.find('[data-testid="denomination-editor"]').exists()).toBe(true)
    })

    it('calculates the total from denomination counts', async () => {
        const wrapper = mountPage()
        await wrapper.get('[data-testid="service-selector"]').trigger('click')
        await wrapper.get('[data-testid="toggle-denominations"]').trigger('click')
        await wrapper.get('[data-denomination="500"]').setValue('2')
        await wrapper.get('[data-denomination="50"]').setValue('3')

        expect(wrapper.get('[data-testid="calculated-total"]').text()).toContain('11,50 €')
        expect(wrapper.get('[data-testid="offering-amount"]').element.value).toBe('11,50')
    })

    it('submits the selected service and denomination counts', async () => {
        const wrapper = mountPage()
        const post = wrapper.vm.$inertia.post
        window.history.replaceState({}, '', '/opfer/1?expires=9999999999&signature=test-signature')
        await wrapper.get('[data-testid="service-selector"]').trigger('click')
        await wrapper.get('[data-testid="toggle-denominations"]').trigger('click')
        await wrapper.get('[data-denomination="2000"]').setValue('1')
        await wrapper.get('form').trigger('submit.prevent')

        expect(post).toHaveBeenCalledWith(
            '/offerings.save?expires=9999999999&signature=test-signature',
            expect.objectContaining({
                service_id: 10,
                offering_denominations: expect.objectContaining({ 2000: 1 }),
            }),
        )
    })

    it('returns from the entry view to service selection', async () => {
        const wrapper = mountPage()

        await wrapper.get('[data-testid="service-selector"]').trigger('click')
        expect(wrapper.find('[data-testid="offering-entry-view"]').exists()).toBe(true)

        await wrapper.get('[data-testid="back-to-services"]').trigger('click')

        expect(wrapper.find('[data-testid="service-selection-view"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="offering-entry-view"]').exists()).toBe(false)
    })

    it('shows the fallback explanation when there are no services today', () => {
        const wrapper = mountPage({ services: [], fallback: true })

        expect(wrapper.text()).toContain('Heute finden keine Gottesdienste statt')
    })

    it('does not offer a saved service for editing', () => {
        const wrapper = mountPage({
            services: [{ ...service, offering_amount: '12.50' }],
        })

        expect(wrapper.text()).not.toContain('Gottesdienst')
        expect(wrapper.find('[data-testid="service-selector"]').exists()).toBe(false)
    })

    it('shows a confirmation screen after the offering was saved', async () => {
        const wrapper = mountPage(
            {},
            { post: vi.fn() },
            { success: 'Die Opferangaben wurden gespeichert.' },
        )

        expect(wrapper.get('[data-testid="save-confirmation"]').text()).toContain(
            'Opferangaben wurden gespeichert',
        )
        expect(wrapper.find('[data-testid="service-selection-view"]').exists()).toBe(false)

        await wrapper.get('[data-testid="continue-entry"]').trigger('click')

        expect(wrapper.find('[data-testid="save-confirmation"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="service-selection-view"]').exists()).toBe(true)
    })
})

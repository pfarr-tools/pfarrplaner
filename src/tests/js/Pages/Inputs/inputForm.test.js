import { mount } from '@vue/test-utils'
import OfferingsForm from '@/Pages/Inputs/Offerings/Form.vue'

const stubs = {
    'admin-layout': {
        template: '<div><slot name="navbar-left" /><slot name="after-flash" /><slot /></div>',
    },
    FormInput: { template: '<input />', props: ['modelValue'] },
    FormRadioGroup: { template: '<div />', props: ['items', 'modelValue'] },
    NavButton: {
        template: '<button @click="$emit(\'click\')"><slot /></button>',
        emits: ['click'],
    },
    Dataset: { template: '<div><slot :ds="{}" /></div>' },
    DatasetItem: { template: '<tbody />' },
    DatasetInfo: { template: '<div />' },
    DatasetPager: { template: '<div />' },
    DatasetShow: { template: '<div />' },
}

const eventStubs = {
    ...stubs,
    FormRadioGroup: {
        template: '<button data-testid="offering-type" @click="$emit(\'input\', \'PO\')" />',
        props: ['items', 'modelValue'],
    },
    DatasetItem: {
        template: '<tbody><slot :row="row" :rowIndex="0" /></tbody>',
        data: () => ({
            row: {
                city: { name: 'Gemeinde A' },
                id: 10255,
                slug: 'service-1',
                date: '2026-01-01',
                locationText: 'Kirche A',
                timeText: '10:00',
                offering_type: 'eO',
                offering_goal: '',
                offering_description: '',
                offerings_counter1: '',
                offerings_counter2: '',
                offering_amount: '',
            },
        }),
    },
}

describe('Offerings Form', () => {
    it('toggles the beamer view from the top navigation', async () => {
        const wrapper = mount(OfferingsForm, {
            props: { services: [] },
            global: { stubs },
        })

        expect(wrapper.vm.beamerView).toBe(false)
        await wrapper.find('button').trigger('click')
        expect(wrapper.vm.beamerView).toBe(true)
        expect(wrapper.find('.beamer-view').exists()).toBe(true)
    })

    it('keeps the offering goal and type visible in beamer view', async () => {
        const wrapper = mount(OfferingsForm, {
            props: {
                services: [
                    {
                        city: { name: 'Gemeinde A' },
                        id: 10255,
                        slug: 'service-1',
                        date: '2026-01-01',
                        locationText: 'Kirche A',
                        timeText: '10:00',
                        offering_type: 'PO',
                        offering_goal: 'Brot für die Welt',
                        offering_description: 'Anmerkung',
                        offerings_counter1: '',
                        offerings_counter2: '',
                        offering_amount: '42,00',
                    },
                ],
            },
            global: {
                stubs: {
                    ...stubs,
                    FormInput: { template: '<input data-testid="offering-goal" />' },
                    FormRadioGroup: { template: '<div data-testid="offering-type" />' },
                },
            },
        })

        await wrapper.find('button').trigger('click')

        expect(wrapper.findAll('th').map((header) => header.text())).toEqual([
            'Gottesdienst',
            'Opferzweck',
        ])
        expect(wrapper.find('tbody tr').findAll('td')).toHaveLength(2)
        expect(wrapper.find('[data-testid="offering-goal"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="offering-type"]').exists()).toBe(true)
    })

    it('uses the services supplied with the Inertia page', () => {
        const services = [{ id: 10255, offering_type: 'PO', city: { name: 'Gemeinde A' } }]
        const wrapper = mount(OfferingsForm, {
            props: { services },
            global: { stubs },
        })

        expect(window.axios.get).not.toHaveBeenCalled()
        expect(wrapper.vm.services).toEqual(services)
    })

    it('saves the newly selected offering type', async () => {
        window.axios.post.mockResolvedValueOnce({ data: 'service-1' })
        const wrapper = mount(OfferingsForm, {
            props: { services: [] },
            global: { stubs },
        })
        const service = { id: 10255, offering_type: 'eO' }

        service.offering_type = 'PO'
        await wrapper.vm.saveService(service)

        expect(window.axios.post).toHaveBeenCalledWith('/inputs.save', {
            id: 10255,
            offering_goal: '',
            offering_type: 'PO',
            offering_description: '',
            offerings_counter1: '',
            offerings_counter2: '',
            offering_amount: '',
        })
    })

    it('saves when the offering type input event is emitted', async () => {
        window.axios.post.mockResolvedValueOnce({ data: 'service-1' })
        const wrapper = mount(OfferingsForm, {
            props: {
                services: [
                    {
                        city: { name: 'Gemeinde A' },
                        id: 10255,
                        slug: 'service-1',
                        date: '2026-01-01',
                        locationText: 'Kirche A',
                        timeText: '10:00',
                        offering_type: 'eO',
                        offering_goal: '',
                        offering_description: '',
                        offerings_counter1: '',
                        offerings_counter2: '',
                        offering_amount: '',
                    },
                ],
            },
            global: { stubs: eventStubs },
        })

        await wrapper.get('[data-testid="offering-type"]').trigger('click')

        expect(window.axios.post).toHaveBeenCalledWith('/inputs.save', {
            id: 10255,
            offering_goal: '',
            offering_type: 'PO',
            offering_description: '',
            offerings_counter1: '',
            offerings_counter2: '',
            offering_amount: '',
        })
    })
})

import { mount } from '@vue/test-utils'
import OfferingsForm from '@/Pages/Inputs/Offerings/Form.vue'

const stubs = {
    AdminLayout: { template: '<div><slot name="after-flash" /><slot /></div>' },
    FormInput: { template: '<input />', props: ['modelValue'] },
    FormSelectize: { template: '<select />', props: ['options', 'modelValue'] },
    NavButton: { template: '<button><slot /></button>' },
    Dataset: { template: '<div><slot :ds="{}" /></div>' },
    DatasetItem: { template: '<tbody />' },
    DatasetInfo: { template: '<div />' },
    DatasetPager: { template: '<div />' },
    DatasetShow: { template: '<div />' },
}

const eventStubs = {
    ...stubs,
    FormSelectize: {
        template: '<button data-testid="offering-type" @click="$emit(\'input\', \'PO\')" />',
        props: ['options', 'modelValue'],
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
    it('uses the services supplied with the Inertia page', () => {
        const services = [{ id: 10255, offering_type: 'PO' }]
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
            props: { services: [] },
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

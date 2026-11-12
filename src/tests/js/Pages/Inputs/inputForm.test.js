import { flushPromises, mount } from '@vue/test-utils'
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
    it('loads the offering type for each service', async () => {
        window.axios.get.mockResolvedValueOnce({
            data: {
                slug: 'service-1',
                date: '2026-01-01',
                offering_type: 'PO',
                city: { name: 'Gemeinde A' },
                locationText: 'Kirche A',
                timeText: '10:00',
            },
        })

        mount(OfferingsForm, {
            props: { serviceSlugs: ['service-1'] },
            global: { stubs },
        })

        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/service.data', {
            params: {
                fields: expect.arrayContaining(['offering_type']),
                load: ['location:id,name', 'city:id,name'],
                append: ['locationText', 'timeText'],
            },
        })
    })

    it('saves the newly selected offering type', async () => {
        window.axios.post.mockResolvedValueOnce({ data: 'service-1' })
        const wrapper = mount(OfferingsForm, {
            props: { serviceSlugs: [] },
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
            props: { serviceSlugs: [] },
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

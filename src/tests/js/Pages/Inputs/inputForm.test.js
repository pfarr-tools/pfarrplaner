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
                load: ['location', 'city'],
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
        const service = { slug: 'service-1', offering_type: 'eO' }

        await wrapper.vm.saveService(service, 'PO')

        expect(window.axios.post).toHaveBeenCalledWith('/inputs.save', {
            slug: 'service-1',
            offering_type: 'PO',
        })
    })
})

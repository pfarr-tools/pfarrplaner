import { mount } from '@vue/test-utils'
import DatasetShow from '@/components/Ui/dataset/DatasetShow.vue'

describe('DatasetShow', () => {
    it('calls showEntries with default dsShowEntries on created()', () => {
        const showEntries = vi.fn()
        mount(DatasetShow, { global: { provide: { showEntries } } })
        expect(showEntries).toHaveBeenCalledWith(10)
    })
    it('calls showEntries with custom dsShowEntries prop', () => {
        const showEntries = vi.fn()
        mount(DatasetShow, {
            props: { dsShowEntries: 25 },
            global: { provide: { showEntries } },
        })
        expect(showEntries).toHaveBeenCalledWith(25)
    })
    it('renders a select element', () => {
        const showEntries = vi.fn()
        const w = mount(DatasetShow, { global: { provide: { showEntries } } })
        expect(w.find('select').exists()).toBe(true)
    })
    it('renders default LOV options', () => {
        const showEntries = vi.fn()
        const w = mount(DatasetShow, { global: { provide: { showEntries } } })
        const options = w.findAll('option')
        expect(options.some(o => o.element.value === '10')).toBe(true)
        expect(options.some(o => o.element.value === '50')).toBe(true)
    })
    it('calls showEntries and emits changed on select change', async () => {
        const showEntries = vi.fn()
        const w = mount(DatasetShow, { global: { provide: { showEntries } } })
        const select = w.find('select')
        select.element.value = '50'
        await select.trigger('change')
        expect(showEntries).toHaveBeenCalledWith(50)
        expect(w.emitted('changed')?.[0]).toEqual([50])
    })
})

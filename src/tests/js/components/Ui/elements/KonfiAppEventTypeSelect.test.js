import { mount, flushPromises } from '@vue/test-utils'
import KonfiAppEventTypeSelect from '@/components/Ui/elements/KonfiAppEventTypeSelect.vue'

const city = { id: 5 }

function mountSelect(apiMock = { get: vi.fn(() => Promise.resolve({ data: [] })) }, props = {}) {
    return mount(KonfiAppEventTypeSelect, {
        props: { name: 'type', city, modelValue: null, ...props },
        global: {
            stubs: { FormSelectize: true },
            mocks: { $api: () => apiMock },
        },
    })
}

describe('KonfiAppEventTypeSelect', () => {
    it('calls axios.get with the city API URL on created()', () => {
        const apiMock = { get: vi.fn(() => Promise.resolve({ data: [] })) }
        mountSelect(apiMock)
        expect(apiMock.get).toHaveBeenCalledWith('/api/city/5/konfiapp-types')
    })
    it('populates items from axios response', async () => {
        const apiMock = { get: vi.fn(() => Promise.resolve({ data: [{ id: 1, name: 'Konfirmation' }] })) }
        const w = mountSelect(apiMock)
        await flushPromises()
        expect(w.vm.items).toEqual([{ id: 1, name: 'Konfirmation' }])
    })
    it('initialises myValue from modelValue', () => {
        const w = mountSelect(undefined, { modelValue: 7 })
        expect(w.vm.myValue).toBe(7)
    })
    it('handleInput emits e.id as update:modelValue', () => {
        const w = mountSelect()
        w.vm.handleInput({ id: 3, name: 'Taufe' })
        expect(w.emitted('update:modelValue')?.[0]).toEqual([3])
        expect(w.emitted('input')?.[0]).toEqual([3])
    })
    it('handleInput keeps scalar ids unchanged', () => {
        const w = mountSelect()
        w.vm.handleInput(4)
        expect(w.emitted('update:modelValue')?.[0]).toEqual([4])
        expect(w.emitted('input')?.[0]).toEqual([4])
    })
})

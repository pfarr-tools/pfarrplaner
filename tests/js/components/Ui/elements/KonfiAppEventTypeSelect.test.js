import { mount, flushPromises } from '@vue/test-utils'
import KonfiAppEventTypeSelect from '@/components/Ui/elements/KonfiAppEventTypeSelect.vue'

const city = { id: 5 }

describe('KonfiAppEventTypeSelect', () => {
    it('calls axios.get with the city API URL on created()', () => {
        mount(KonfiAppEventTypeSelect, {
            props: { name: 'type', city, modelValue: null },
            global: { stubs: { FormSelectize: true } },
        })
        expect(window.axios.get).toHaveBeenCalledWith('/api/city/5/konfiapp-types')
    })
    it('populates items from axios response', async () => {
        window.axios.get.mockResolvedValue({ data: [{ id: 1, name: 'Konfirmation' }] })
        const w = mount(KonfiAppEventTypeSelect, {
            props: { name: 'type', city, modelValue: null },
            global: { stubs: { FormSelectize: true } },
        })
        await flushPromises()
        expect(w.vm.items).toEqual([{ id: 1, name: 'Konfirmation' }])
    })
    it('initialises myValue from modelValue', () => {
        const w = mount(KonfiAppEventTypeSelect, {
            props: { name: 'type', city, modelValue: 7 },
            global: { stubs: { FormSelectize: true } },
        })
        expect(w.vm.myValue).toBe(7)
    })
    it('handleInput emits e.id as update:modelValue', () => {
        const w = mount(KonfiAppEventTypeSelect, {
            props: { name: 'type', city, modelValue: null },
            global: { stubs: { FormSelectize: true } },
        })
        w.vm.handleInput({ id: 3, name: 'Taufe' })
        expect(w.emitted('update:modelValue')?.[0]).toEqual([3])
        expect(w.emitted('input')?.[0]).toEqual([3])
    })
})

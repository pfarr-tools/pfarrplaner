import { mount } from '@vue/test-utils'
import LocationSelect from '@/components/Ui/elements/LocationSelect.vue'

const locations = [
    { id: 1, name: 'Stadtkirche', city: { name: 'Musterstadt' } },
    { id: 2, name: 'Gemeindehaus', city: { name: 'Musterstadt' } },
    { id: 3, name: 'Kapelle', city: { name: 'Nebenort' } },
]

const stubs = { FormGroup: { template: '<div><slot /></div>' }, Multiselect: true }

function mountIt(props = {}) {
    return mount(LocationSelect, {
        props: { name: 'location_id', locations, ...props },
        global: { stubs },
    })
}

describe('LocationSelect', () => {
    describe('groupedOptions', () => {
        it('builds one group per city', () => {
            const w = mountIt()
            const labels = w.vm.groupedOptions.map(g => g.label)
            expect(labels).toContain('Musterstadt')
            expect(labels).toContain('Nebenort')
        })

        it('puts locations in the correct city group', () => {
            const w = mountIt()
            const musterGroup = w.vm.groupedOptions.find(g => g.label === 'Musterstadt')
            expect(musterGroup.options).toHaveLength(2)
        })

        it('always includes a Freie Ortsangabe group', () => {
            const w = mountIt()
            const freiGroup = w.vm.groupedOptions.find(g => g.label === 'Freie Ortsangabe')
            expect(freiGroup).toBeDefined()
        })

        it('updates groups when locations prop changes', async () => {
            const w = mountIt({ locations: [locations[0]] })
            expect(w.vm.groupedOptions.find(g => g.label === 'Musterstadt')?.options).toHaveLength(1)

            await w.setProps({ locations: [locations[2]] })

            expect(w.vm.groupedOptions.find(g => g.label === 'Musterstadt')).toBeUndefined()
            expect(w.vm.groupedOptions.find(g => g.label === 'Nebenort')?.options).toHaveLength(1)
        })

        it('adds freetext initial value to Freie Ortsangabe group', () => {
            const w = mountIt({ modelValue: 'Gemeindesaal Süd' })
            const freiGroup = w.vm.groupedOptions.find(g => g.label === 'Freie Ortsangabe')
            expect(freiGroup.options).toHaveLength(1)
            expect(freiGroup.options[0]).toEqual({ id: 'Gemeindesaal Süd', name: 'Gemeindesaal Süd' })
        })

        it('does not add numeric initial value to Freie Ortsangabe group', () => {
            const w = mountIt({ modelValue: 1 })
            const freiGroup = w.vm.groupedOptions.find(g => g.label === 'Freie Ortsangabe')
            expect(freiGroup.options).toHaveLength(0)
        })
    })

    describe('initial myValue', () => {
        it('is null when no value provided', () => {
            const w = mountIt()
            expect(w.vm.myValue).toBeNull()
        })

        it('uses id from object modelValue', () => {
            const w = mountIt({ modelValue: locations[0] })
            expect(w.vm.myValue).toBe(1)
        })

        it('uses numeric modelValue directly', () => {
            const w = mountIt({ modelValue: 2 })
            expect(w.vm.myValue).toBe(2)
        })

        it('uses freetext string modelValue directly', () => {
            const w = mountIt({ modelValue: 'Außenstelle' })
            expect(w.vm.myValue).toBe('Außenstelle')
        })
    })

    describe('locationChanged', () => {
        it('emits set-location null when cleared', () => {
            const w = mountIt()
            w.vm.locationChanged(null)
            expect(w.emitted('set-location')?.[0]).toEqual([null])
        })

        it('emits set-location with location object when returnObject and numeric id', () => {
            const w = mountIt({ returnObject: true })
            w.vm.locationChanged(1)
            expect(w.emitted('set-location')?.[0]).toEqual([locations[0]])
        })

        it('emits set-location with freetext string when returnObject and non-numeric', () => {
            const w = mountIt({ returnObject: true })
            w.vm.locationChanged('Gemeindesaal')
            expect(w.emitted('set-location')?.[0]).toEqual(['Gemeindesaal'])
        })

        it('emits set-location null when undefined', () => {
            const w = mountIt()
            w.vm.locationChanged(undefined)
            expect(w.emitted('set-location')?.[0]).toEqual([null])
        })

        it('emits input and update:modelValue when useInput is true', () => {
            const w = mountIt({ useInput: true, returnObject: true })
            w.vm.locationChanged(1)
            expect(w.emitted('input')?.[0]).toEqual([locations[0]])
            expect(w.emitted('update:modelValue')?.[0]).toEqual([locations[0]])
        })
    })
})

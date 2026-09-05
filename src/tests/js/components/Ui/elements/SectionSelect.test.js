import { mount } from '@vue/test-utils'
import SectionSelect from '@/components/Ui/elements/SectionSelect.vue'

const location = {
    seating_sections: [
        { title: 'Mittelschiff' },
        { title: 'Empore' },
    ],
}

describe('SectionSelect', () => {
    it('renders section-select root div', () => {
        const w = mount(SectionSelect, {
            props: { name: 'section', location, modelValue: null },
            global: { stubs: { FormSelectize: true } },
        })
        expect(w.find('.section-select').exists()).toBe(true)
    })
    it('myItems returns seating_sections from location', () => {
        const w = mount(SectionSelect, {
            props: { name: 'section', location, modelValue: null },
            global: { stubs: { FormSelectize: true } },
        })
        expect(w.vm.myItems).toEqual(location.seating_sections)
    })
    it('handles location without seating_sections', () => {
        const w = mount(SectionSelect, {
            props: { name: 'section', location: { name: 'Kirche' }, modelValue: null },
            global: { stubs: { FormSelectize: true } },
        })
        expect(w.vm.myItems).toEqual([])
    })
    it('handleInput emits comma-joined string when multiple=true', () => {
        const w = mount(SectionSelect, {
            props: { name: 'section', location, modelValue: null, multiple: true },
            global: { stubs: { FormSelectize: true } },
        })
        w.vm.handleInput(['Mittelschiff', 'Empore'])
        expect(w.emitted('update:modelValue')?.[0]).toEqual(['Mittelschiff,Empore'])
        expect(w.emitted('input')?.[0]).toEqual(['Mittelschiff,Empore'])
    })
    it('handleInput emits value directly when multiple=false', () => {
        const w = mount(SectionSelect, {
            props: { name: 'section', location, modelValue: null, multiple: false },
            global: { stubs: { FormSelectize: true } },
        })
        w.vm.handleInput('Mittelschiff')
        expect(w.emitted('update:modelValue')?.[0]).toEqual(['Mittelschiff'])
    })
    it('splits modelValue on comma for multiple mode', () => {
        const w = mount(SectionSelect, {
            props: { name: 'section', location, modelValue: 'Mittelschiff,Empore', multiple: true },
            global: { stubs: { FormSelectize: true } },
        })
        expect(w.vm.myValue).toEqual(['Mittelschiff', 'Empore'])
    })
})

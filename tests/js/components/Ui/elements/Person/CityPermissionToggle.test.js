import { mount } from '@vue/test-utils'
import CityPermissionToggle from '@/components/Ui/elements/Person/CityPermissionToggle.vue'

describe('CityPermissionToggle', () => {
    it('renders 4 radio buttons', () => {
        const w = mount(CityPermissionToggle, { props: { modelValue: 'n' } })
        expect(w.findAll('input[type="radio"]')).toHaveLength(4)
    })
    it('initialises rights data from modelValue', () => {
        const w = mount(CityPermissionToggle, { props: { modelValue: 'w' } })
        expect(w.vm.rights).toBe('w')
    })
    it('active class on the label matching modelValue', () => {
        const w = mount(CityPermissionToggle, { props: { modelValue: 'r' } })
        expect(w.find('.rights-read').classes()).toContain('active')
    })
    it('emits update:modelValue when radio triggers input', async () => {
        const w = mount(CityPermissionToggle, { props: { modelValue: 'n' } })
        const radio = w.find('input[value="r"]')
        radio.element.checked = true
        await radio.trigger('input')
        expect(w.emitted('update:modelValue')?.[0]).toEqual(['r'])
    })
    it('emits input when radio triggers input', async () => {
        const w = mount(CityPermissionToggle, { props: { modelValue: 'n' } })
        const radio = w.find('input[value="a"]')
        radio.element.checked = true
        await radio.trigger('input')
        expect(w.emitted('input')?.[0]).toEqual(['a'])
    })
    it('has radio inputs for n, r, w, a values', () => {
        const w = mount(CityPermissionToggle, { props: { modelValue: 'n' } })
        expect(w.find('input[value="n"]').exists()).toBe(true)
        expect(w.find('input[value="r"]').exists()).toBe(true)
        expect(w.find('input[value="w"]').exists()).toBe(true)
        expect(w.find('input[value="a"]').exists()).toBe(true)
    })
})

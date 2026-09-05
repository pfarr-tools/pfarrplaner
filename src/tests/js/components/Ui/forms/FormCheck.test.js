import { mount } from '@vue/test-utils'
import FormCheck from '@/components/Ui/forms/FormCheck.vue'

describe('FormCheck', () => {
    it('renders a checkbox', () => {
        const w = mount(FormCheck, { props: { name: 'active', modelValue: 0 } })
        expect(w.find('input[type="checkbox"]').exists()).toBe(true)
    })
    it('renders label when prop provided', () => {
        const w = mount(FormCheck, { props: { name: 'active', label: 'Aktiv', modelValue: 0 } })
        expect(w.find('label').text()).toBe('Aktiv')
    })
    it('is checked when modelValue is truthy', () => {
        const w = mount(FormCheck, { props: { name: 'active', modelValue: 1 } })
        expect(w.find('input[type="checkbox"]').element.checked).toBe(true)
    })
    it('is unchecked when modelValue is 0', () => {
        const w = mount(FormCheck, { props: { name: 'active', modelValue: 0 } })
        expect(w.find('input[type="checkbox"]').element.checked).toBe(false)
    })
    it('emits update:modelValue with 1 when checked', async () => {
        const w = mount(FormCheck, { props: { name: 'active', modelValue: 0 } })
        await w.find('input[type="checkbox"]').setChecked(true)
        expect(w.emitted('update:modelValue')[0]).toEqual([1])
    })
    it('emits update:modelValue with 0 when unchecked', async () => {
        const w = mount(FormCheck, { props: { name: 'active', modelValue: 1 } })
        await w.find('input[type="checkbox"]').setChecked(false)
        expect(w.emitted('update:modelValue')[0]).toEqual([0])
    })
    it('shows validation error from $page.props.errors', () => {
        const w = mount(FormCheck, {
            props: { name: 'active', modelValue: 0 },
            global: {
                mocks: { $page: { props: { errors: { active: 'Pflichtfeld.' } } } },
            },
        })
        expect(w.find('.invalid-feedback').text()).toBe('Pflichtfeld.')
    })
})

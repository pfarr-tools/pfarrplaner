import { mount } from '@vue/test-utils'
import FormInput from '@/components/Ui/forms/FormInput.vue'

describe('FormInput', () => {
    it('renders a text input by default', () => {
        const w = mount(FormInput, { props: { name: 'title', modelValue: '' } })
        expect(w.find('input[type="text"]').exists()).toBe(true)
    })
    it('renders an input of the specified type', () => {
        const w = mount(FormInput, { props: { name: 'count', type: 'number', modelValue: 0 } })
        expect(w.find('input[type="number"]').exists()).toBe(true)
    })
    it('renders label inside form-group', () => {
        const w = mount(FormInput, { props: { name: 'title', label: 'Titel', modelValue: '' } })
        expect(w.find('label').text()).toBe('Titel')
    })
    it('displays the current value', () => {
        const w = mount(FormInput, { props: { name: 'title', modelValue: 'Hello' } })
        expect(w.find('input').element.value).toBe('Hello')
    })
    it('emits update:modelValue on input', async () => {
        const w = mount(FormInput, { props: { name: 'title', modelValue: '' } })
        await w.find('input').setValue('New value')
        const emits = w.emitted('update:modelValue')
        expect(emits).toBeTruthy()
        expect(emits[emits.length - 1]).toEqual(['New value'])
    })
    it('is disabled when disabled prop is set', () => {
        const w = mount(FormInput, { props: { name: 'title', modelValue: '', disabled: true } })
        expect(w.find('input').attributes('disabled')).toBeDefined()
    })
    it('renders placeholder attribute', () => {
        const w = mount(FormInput, { props: { name: 'title', modelValue: '', placeholder: 'Enter title' } })
        expect(w.find('input').attributes('placeholder')).toBe('Enter title')
    })
    it('marks input is-invalid when errors[name] is set', () => {
        const w = mount(FormInput, {
            props: { name: 'title', modelValue: '' },
            global: { mocks: { $page: { props: { errors: { title: 'Pflichtfeld.' } } } } },
        })
        expect(w.find('input').classes()).toContain('is-invalid')
    })
})

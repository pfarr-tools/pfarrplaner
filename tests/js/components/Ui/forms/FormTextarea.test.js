import { mount } from '@vue/test-utils'
import FormTextarea from '@/components/Ui/forms/FormTextarea.vue'

describe('FormTextarea', () => {
    it('renders a textarea', () => {
        expect(mount(FormTextarea, { props: { name: 'body', modelValue: '' } }).find('textarea').exists()).toBe(true)
    })
    it('defaults to 5 rows', () => {
        const w = mount(FormTextarea, { props: { name: 'body', modelValue: '' } })
        expect(w.find('textarea').attributes('rows')).toBe('5')
    })
    it('uses the specified row count', () => {
        const w = mount(FormTextarea, { props: { name: 'body', modelValue: '', rows: 10 } })
        expect(w.find('textarea').attributes('rows')).toBe('10')
    })
    it('renders label', () => {
        const w = mount(FormTextarea, { props: { name: 'body', label: 'Inhalt', modelValue: '' } })
        expect(w.find('label').text()).toBe('Inhalt')
    })
    it('displays the current value', () => {
        const w = mount(FormTextarea, { props: { name: 'body', modelValue: 'Some text' } })
        expect(w.find('textarea').element.value).toBe('Some text')
    })
    it('emits update:modelValue on input', async () => {
        const w = mount(FormTextarea, { props: { name: 'body', modelValue: '' } })
        await w.find('textarea').setValue('New text')
        const emits = w.emitted('update:modelValue')
        expect(emits[emits.length - 1]).toEqual(['New text'])
    })
    it('is disabled when disabled prop is set', () => {
        const w = mount(FormTextarea, { props: { name: 'body', modelValue: '', disabled: true } })
        expect(w.find('textarea').attributes('disabled')).toBeDefined()
    })
    it('marks textarea is-invalid when errors[name] is set', () => {
        const w = mount(FormTextarea, {
            props: { name: 'body', modelValue: '' },
            global: { mocks: { $page: { props: { errors: { body: 'Pflichtfeld.' } } } } },
        })
        expect(w.find('textarea').classes()).toContain('is-invalid')
    })
})

import { mount } from '@vue/test-utils'
import FormRadioGroup from '@/components/Ui/forms/FormRadioGroup.vue'

const items = { '1': 'Eins', '2': 'Zwei', '3': 'Drei' }

describe('FormRadioGroup', () => {
    it('renders a radio button per item', () => {
        const w = mount(FormRadioGroup, { props: { name: 'choice', items, modelValue: '' } })
        expect(w.findAll('input[type="radio"]')).toHaveLength(3)
    })
    it('renders a label per item', () => {
        const w = mount(FormRadioGroup, { props: { name: 'choice', items, modelValue: '' } })
        const labels = w.findAll('label')
        expect(labels.some(l => l.text() === 'Eins')).toBe(true)
        expect(labels.some(l => l.text() === 'Zwei')).toBe(true)
    })
    it('pre-selects the current modelValue', () => {
        const w = mount(FormRadioGroup, { props: { name: 'choice', items, modelValue: '2' } })
        const radios = w.findAll('input[type="radio"]')
        const checked = radios.find(r => r.element.checked)
        expect(checked.element.value).toBe('2')
    })
    it('renders label when label prop is set', () => {
        const w = mount(FormRadioGroup, { props: { name: 'choice', items, modelValue: '', label: 'Auswahl' } })
        expect(w.find('label').text()).toBe('Auswahl')
    })
    it('is disabled when disabled prop is set', () => {
        const w = mount(FormRadioGroup, { props: { name: 'choice', items, modelValue: '', disabled: true } })
        w.findAll('input[type="radio"]').forEach(r => {
            expect(r.attributes('disabled')).toBeDefined()
        })
    })
    it('emits update:modelValue on radio selection', async () => {
        const w = mount(FormRadioGroup, { props: { name: 'choice', items, modelValue: '' } })
        const radio = w.find('input[value="1"]')
        // changed() only emits when event.target.checked is true
        radio.element.checked = true
        await radio.trigger('input')
        expect(w.emitted('update:modelValue')).toBeTruthy()
    })
    it('marks inputs is-invalid when $page.props.errors[name] is set', () => {
        const w = mount(FormRadioGroup, {
            props: { name: 'choice', items, modelValue: '' },
            global: { mocks: { $page: { props: { errors: { choice: 'Bitte auswählen.' } } } } },
        })
        expect(w.find('input[type="radio"]').classes()).toContain('is-invalid')
    })
})

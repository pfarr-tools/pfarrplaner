import { mount } from '@vue/test-utils'
import FormGroup from '@/components/Ui/forms/FormGroup.vue'

describe('FormGroup', () => {
    it('renders slot content inside .form-group', () => {
        const w = mount(FormGroup, {
            props: { name: 'field', id: 'field' },
            slots: { default: '<input />' },
        })
        expect(w.classes()).toContain('form-group')
        expect(w.find('input').exists()).toBe(true)
    })
    it('renders label when label prop is provided', () => {
        const w = mount(FormGroup, { props: { name: 'field', id: 'field', label: 'My Label' } })
        expect(w.find('label').text()).toBe('My Label')
    })
    it('does not render label without label prop', () => {
        const w = mount(FormGroup, { props: { name: 'field', id: 'field' } })
        expect(w.find('label').exists()).toBe(false)
    })
    it('adds form-group-required class when required prop is set', () => {
        const w = mount(FormGroup, { props: { name: 'field', id: 'field', required: true } })
        expect(w.classes()).toContain('form-group-required')
    })
    it('renders help text when no error and help prop is set', () => {
        const w = mount(FormGroup, { props: { name: 'field', id: 'field', help: 'Hint text' } })
        expect(w.find('.form-text').text()).toBe('Hint text')
    })
    it('shows validation error from $page.props.errors', () => {
        const w = mount(FormGroup, {
            props: { name: 'field', id: 'field' },
            global: { mocks: { $page: { props: { errors: { field: ['Pflichtfeld.'] } } } } },
        })
        expect(w.find('.invalid-feedback').text()).toBe('Pflichtfeld.')
    })
})

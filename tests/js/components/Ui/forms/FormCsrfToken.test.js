import { mount } from '@vue/test-utils'
import FormCsrfToken from '@/components/Ui/forms/FormCsrfToken.vue'

describe('FormCsrfToken', () => {
    it('renders a hidden input with name _token', () => {
        const w = mount(FormCsrfToken, {
            global: { mocks: { $page: { props: { csrfToken: 'abc123' } } } },
        })
        const input = w.find('input[type="hidden"]')
        expect(input.exists()).toBe(true)
        expect(input.attributes('name')).toBe('_token')
    })
    it('uses $page.props.csrfToken as value', () => {
        const w = mount(FormCsrfToken, {
            global: { mocks: { $page: { props: { csrfToken: 'tok-xyz' } } } },
        })
        expect(w.find('input').attributes('value')).toBe('tok-xyz')
    })
})

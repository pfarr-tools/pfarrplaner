import { mount } from '@vue/test-utils'
import QuillDropdown from '@/components/LiturgyEditor/Editors/Quill/QuillDropdown.vue'

describe('QuillDropdown', () => {
    it('renders non-empty text building blocks', () => {
        const wrapper = mount(QuillDropdown, {
            props: {
                label: 'Müller',
                items: {
                    Sterbedatum: '24.05.2026',
                    Sterbeort: '',
                    Rufname: 'Max',
                },
            },
        })

        wrapper.find('button.dropdown-toggle').trigger('click')

        expect(wrapper.findAll('.dropdown-item')).toHaveLength(2)
        expect(wrapper.text()).toContain('Sterbedatum')
        expect(wrapper.text()).toContain('Rufname')
    })
})

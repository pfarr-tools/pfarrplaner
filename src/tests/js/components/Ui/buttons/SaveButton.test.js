import { mount } from '@vue/test-utils'
import SaveButton from '@/components/Ui/buttons/SaveButton.vue'

describe('SaveButton', () => {
    it('renders a primary button', () => {
        expect(mount(SaveButton).find('a.btn-primary').exists()).toBe(true)
    })
    it('shows default label "Speichern"', () => {
        expect(mount(SaveButton).text()).toContain('Speichern')
    })
    it('shows custom label when provided', () => {
        expect(mount(SaveButton, { props: { label: 'Sichern' } }).text()).toContain('Sichern')
    })
    it('has the save icon', () => {
        expect(mount(SaveButton).find('span.mdi-content-save').exists()).toBe(true)
    })
    it('emits "click" when clicked', async () => {
        const w = mount(SaveButton)
        await w.find('a').trigger('click')
        expect(w.emitted('click')).toHaveLength(1)
    })
})

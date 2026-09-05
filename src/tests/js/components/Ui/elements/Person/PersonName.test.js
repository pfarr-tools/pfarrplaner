import { mount } from '@vue/test-utils'
import PersonName from '@/components/Ui/elements/Person/PersonName.vue'

describe('PersonName', () => {
    const person = { first_name: 'Christoph', last_name: 'Fischer', name: 'Christoph Fischer' }

    it('shows first and last name in default order', () => {
        const w = mount(PersonName, { props: { person } })
        expect(w.text()).toContain('Christoph')
        expect(w.text()).toContain('Fischer')
    })
    it('shows "last, first" when lastFirst is set', () => {
        const w = mount(PersonName, { props: { person, lastFirst: true } })
        const text = w.text()
        expect(text.indexOf('Fischer')).toBeLessThan(text.indexOf('Christoph'))
    })
    it('applies text-bold class to last name when lastBold is set', () => {
        const w = mount(PersonName, { props: { person, lastBold: true } })
        const boldSpan = w.findAll('span').find(s => s.classes('text-bold'))
        expect(boldSpan).toBeDefined()
        expect(boldSpan.text()).toContain('Fischer')
    })
    it('falls back to splitting .name when first_name is absent', () => {
        const p = { name: 'Hans Müller' }
        const w = mount(PersonName, { props: { person: p } })
        expect(w.text()).toContain('Hans')
        expect(w.text()).toContain('Müller')
    })
})

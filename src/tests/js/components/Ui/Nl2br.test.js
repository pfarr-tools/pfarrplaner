import { mount } from '@vue/test-utils'
import Nl2br from '@/components/Ui/Nl2br.vue'

describe('Nl2br', () => {
    it('renders text without newlines', () => {
        const w = mount(Nl2br, { props: { text: 'Hello World' } })
        expect(w.text()).toBe('Hello World')
        expect(w.findAll('br')).toHaveLength(0)
    })
    it('inserts <br> per newline', () => {
        const w = mount(Nl2br, { props: { text: 'A\nB\nC' } })
        expect(w.findAll('br')).toHaveLength(2)
    })
    it('wraps in <span> by default', () => {
        expect(mount(Nl2br, { props: { text: 'x' } }).element.tagName).toBe('SPAN')
    })
    it('wraps in specified tag', () => {
        expect(mount(Nl2br, { props: { text: 'x', tag: 'p' } }).element.tagName).toBe('P')
    })
    it('handles empty text', () => {
        expect(mount(Nl2br, { props: { text: '' } }).text()).toBe('')
    })
})

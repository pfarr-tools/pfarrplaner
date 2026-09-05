import { mount } from '@vue/test-utils'
import Card from '@/components/Ui/cards/card.vue'
import CardHeader from '@/components/Ui/cards/cardHeader.vue'
import CardBody from '@/components/Ui/cards/cardBody.vue'
import CardFooter from '@/components/Ui/cards/cardFooter.vue'

describe('card', () => {
    it('wraps slot in .card div', () => {
        const w = mount(Card, { slots: { default: 'Content' } })
        expect(w.classes()).toContain('card')
        expect(w.text()).toBe('Content')
    })
})

describe('cardHeader', () => {
    it('wraps slot in .card-header div', () => {
        const w = mount(CardHeader, { slots: { default: 'Header' } })
        expect(w.classes()).toContain('card-header')
        expect(w.text()).toBe('Header')
    })
})

describe('cardBody', () => {
    it('wraps slot in .card-body div', () => {
        const w = mount(CardBody, { slots: { default: 'Body' } })
        expect(w.classes()).toContain('card-body')
        expect(w.text()).toBe('Body')
    })
})

describe('cardFooter', () => {
    it('wraps slot in .card-footer div', () => {
        const w = mount(CardFooter, { slots: { default: 'Footer' } })
        expect(w.classes()).toContain('card-footer')
        expect(w.text()).toBe('Footer')
    })
})

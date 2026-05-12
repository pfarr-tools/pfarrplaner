import { mount } from '@vue/test-utils'
import Seat from '@/components/Ui/elements/seating/Seat.vue'

describe('Seat', () => {
    it('uses mdi-sofa icon for numeric title with seats > 1', () => {
        const w = mount(Seat, {
            props: { seat: { title: '12', seats: 4, color: '#fff' }, booking: { fixed_seat: false } },
        })
        expect(w.find('span.fa').classes()).toContain('mdi-sofa')
    })
    it('uses mdi-sofa-single icon for non-numeric title', () => {
        const w = mount(Seat, {
            props: { seat: { title: 'Kanzel', seats: 4, color: '#fff' }, booking: { fixed_seat: false } },
        })
        expect(w.find('span.fa').classes()).toContain('mdi-sofa-single')
    })
    it('uses mdi-sofa-single icon for numeric title with seats = 1', () => {
        const w = mount(Seat, {
            props: { seat: { title: '5', seats: 1, color: '#fff' }, booking: { fixed_seat: false } },
        })
        expect(w.find('span.fa').classes()).toContain('mdi-sofa-single')
    })
    it('applies seat.color as background-color', () => {
        const w = mount(Seat, {
            props: { seat: { title: '5', seats: 1, color: 'red' }, booking: { fixed_seat: false } },
        })
        expect(w.find('.seat').element.style.backgroundColor).toBe('red')
    })
    it('renders seat title', () => {
        const w = mount(Seat, {
            props: { seat: { title: 'Altar', seats: 1, color: '#fff' }, booking: { fixed_seat: false } },
        })
        expect(w.text()).toContain('Altar')
    })
    it('applies bold font-weight when fixed_seat=true', () => {
        const w = mount(Seat, {
            props: { seat: { title: '5', seats: 1, color: '#fff' }, booking: { fixed_seat: true } },
        })
        expect(w.find('.seat').element.style.fontWeight).toBe('bold')
    })
    it('applies italic font-style when fixed_seat=false', () => {
        const w = mount(Seat, {
            props: { seat: { title: '5', seats: 1, color: '#fff' }, booking: { fixed_seat: false } },
        })
        expect(w.find('.seat').element.style.fontStyle).toBe('italic')
    })
})

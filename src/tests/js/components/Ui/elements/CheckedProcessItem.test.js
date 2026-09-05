import { mount } from '@vue/test-utils'
import CheckedProcessItem from '@/components/Ui/elements/CheckedProcessItem.vue'

describe('CheckedProcessItem', () => {
    it('shows positive text when check is truthy', () => {
        const w = mount(CheckedProcessItem, { props: { check: true, positive: 'Ja', negative: 'Nein' } })
        expect(w.text()).toContain('Ja')
        expect(w.text()).not.toContain('Nein')
    })
    it('shows negative text when check is falsy', () => {
        const w = mount(CheckedProcessItem, { props: { check: false, positive: 'Ja', negative: 'Nein' } })
        expect(w.text()).toContain('Nein')
        expect(w.text()).not.toContain('Ja')
    })
    it('uses mdi-check-circle icon for positive by default', () => {
        const w = mount(CheckedProcessItem, { props: { check: true } })
        expect(w.find('span').classes()).toContain('mdi-check-circle')
    })
    it('uses mdi-close-circle icon for negative by default', () => {
        const w = mount(CheckedProcessItem, { props: { check: false } })
        expect(w.find('span').classes()).toContain('mdi-close-circle')
    })
    it('uses custom positive icon class', () => {
        const w = mount(CheckedProcessItem, { props: { check: true, iconPositive: 'mdi mdi-thumb-up' } })
        expect(w.find('span').classes()).toContain('mdi-thumb-up')
    })
    it('uses custom negative icon class', () => {
        const w = mount(CheckedProcessItem, { props: { check: false, iconNegative: 'mdi mdi-thumb-down' } })
        expect(w.find('span').classes()).toContain('mdi-thumb-down')
    })
    it('uses positive slot over positive prop', () => {
        const w = mount(CheckedProcessItem, {
            props: { check: true, positive: 'Prop text' },
            slots: { positive: 'Slot text' },
        })
        expect(w.text()).toContain('Slot text')
    })
    it('applies green color for positive by default', () => {
        const w = mount(CheckedProcessItem, { props: { check: true } })
        expect(w.find('span').attributes('style')).toContain('green')
    })
    it('applies red color for negative by default', () => {
        const w = mount(CheckedProcessItem, { props: { check: false } })
        expect(w.find('span').attributes('style')).toContain('red')
    })
    it('applies custom positive color', () => {
        const w = mount(CheckedProcessItem, { props: { check: true, colorPositive: 'blue' } })
        expect(w.find('span').attributes('style')).toContain('blue')
    })
})

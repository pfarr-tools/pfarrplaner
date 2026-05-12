import { mount } from '@vue/test-utils'
import ValueCheck from '@/components/Ui/elements/ValueCheck.vue'

describe('ValueCheck', () => {
    it('shows mdi-check-circle for truthy value', () => {
        expect(mount(ValueCheck, { props: { value: true } }).classes()).toContain('mdi-check-circle')
    })
    it('shows mdi-close-circle for false', () => {
        expect(mount(ValueCheck, { props: { value: false } }).classes()).toContain('mdi-close-circle')
    })
    it('shows mdi-close-circle for null', () => {
        expect(mount(ValueCheck, { props: { value: null } }).classes()).toContain('mdi-close-circle')
    })
    it('shows mdi-close-circle for 0', () => {
        expect(mount(ValueCheck, { props: { value: 0 } }).classes()).toContain('mdi-close-circle')
    })
    it('shows mdi-check-circle for non-empty string', () => {
        expect(mount(ValueCheck, { props: { value: 'yes' } }).classes()).toContain('mdi-check-circle')
    })
    it('shows mdi-check-circle for positive number', () => {
        expect(mount(ValueCheck, { props: { value: 1 } }).classes()).toContain('mdi-check-circle')
    })
})

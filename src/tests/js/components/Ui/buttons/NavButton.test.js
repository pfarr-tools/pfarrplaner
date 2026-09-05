import { mount } from '@vue/test-utils'
import NavButton from '@/components/Ui/buttons/NavButton.vue'

describe('NavButton', () => {
    it('renders with default "light" class', () => {
        expect(mount(NavButton).classes()).toContain('btn-light')
    })
    it('applies specified type class', () => {
        expect(mount(NavButton, { props: { type: 'primary' } }).classes()).toContain('btn-primary')
    })
    it('emits "click" when clicked without href', async () => {
        const w = mount(NavButton)
        await w.trigger('click')
        expect(w.emitted('click')).toHaveLength(1)
    })
    it('calls $inertia.get() when href is set', async () => {
        const inertiaGet = vi.fn()
        const w = mount(NavButton, {
            props: { href: '/some-path' },
            global: { mocks: { $inertia: { get: inertiaGet } } },
        })
        await w.trigger('click')
        expect(inertiaGet).toHaveBeenCalledWith('/some-path')
        expect(w.emitted('click')).toBeUndefined()
    })
    it('renders slot text', () => {
        const w = mount(NavButton, { slots: { default: 'Save' } })
        expect(w.text()).toContain('Save')
    })
    it('shows icon span when icon prop is set', () => {
        const w = mount(NavButton, { props: { icon: 'mdi mdi-home' } })
        expect(w.find('span.mdi').exists()).toBe(true)
    })
})

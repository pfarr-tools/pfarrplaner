import { mount } from '@vue/test-utils'
import Tab from '@/components/Ui/tabs/tab.vue'
import Tabs from '@/components/Ui/tabs/tabs.vue'

describe('Tab', () => {
    it('has role="tabpanel"', () => {
        expect(mount(Tab, { props: { id: 'tab1' } }).attributes('role')).toBe('tabpanel')
    })
    it('is active when active prop is true', () => {
        const w = mount(Tab, { props: { id: 'tab1', active: true } })
        expect(w.classes()).toContain('active')
        expect(w.classes()).toContain('show')
    })
    it('is active when activeTab matches id', () => {
        const w = mount(Tab, { props: { id: 'tab1', active: false, activeTab: 'tab1' } })
        expect(w.classes()).toContain('active')
        expect(w.classes()).toContain('show')
    })
    it('is not active when active is false and activeTab does not match', () => {
        const w = mount(Tab, { props: { id: 'tab1', active: false, activeTab: 'other' } })
        expect(w.classes()).not.toContain('active')
        expect(w.classes()).not.toContain('show')
    })
    it('sets the id attribute', () => {
        expect(mount(Tab, { props: { id: 'my-tab' } }).attributes('id')).toBe('my-tab')
    })
    it('renders slot content', () => {
        const w = mount(Tab, { props: { id: 'tab1', active: true }, slots: { default: 'Panel content' } })
        expect(w.text()).toBe('Panel content')
    })
})

describe('Tabs', () => {
    it('wraps slot in .tab-content div', () => {
        const w = mount(Tabs, { slots: { default: 'Panels here' } })
        expect(w.classes()).toContain('tab-content')
        expect(w.text()).toBe('Panels here')
    })
})

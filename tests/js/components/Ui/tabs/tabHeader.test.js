import { mount } from '@vue/test-utils'
import TabHeader from '@/components/Ui/tabs/tabHeader.vue'
import TabHeaders from '@/components/Ui/tabs/tabHeaders.vue'

describe('tabHeader', () => {
    it('renders a <li class="nav-item">', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'Tab 1' } })
        expect(w.element.tagName).toBe('LI')
        expect(w.classes()).toContain('nav-item')
    })
    it('shows the title in the nav-link', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'Übersicht' } })
        expect(w.find('a.nav-link').text()).toContain('Übersicht')
    })
    it('shows loading spinner when no title and no icon', () => {
        const w = mount(TabHeader, { props: { id: 'tab1' } })
        expect(w.find('span.mdi-loading').exists()).toBe(true)
        expect(w.find('a').exists()).toBe(false)
    })
    it('is active when active prop is true', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', active: true } })
        expect(w.find('a').classes()).toContain('active')
    })
    it('is active when activeTab matches id', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', activeTab: 'tab1' } })
        expect(w.find('a').classes()).toContain('active')
    })
    it('is not active when active=false and activeTab does not match', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', active: false, activeTab: 'other' } })
        expect(w.find('a').classes()).not.toContain('active')
    })
    it('has disabled class when disabled prop is set', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', disabled: true } })
        expect(w.find('a').classes()).toContain('disabled')
    })
    it('does not show count badge when count is 0', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', count: 0 } })
        expect(w.find('.badge').exists()).toBe(false)
    })
    it('shows count badge when count > 0', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', count: 5 } })
        const badge = w.find('.badge')
        expect(badge.exists()).toBe(true)
        expect(badge.text()).toBe('5')
    })
    it('badge uses badge-primary by default', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', count: 3 } })
        expect(w.find('.badge').classes()).toContain('badge-primary')
    })
    it('badge uses specified badgeType', () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T', count: 3, badgeType: 'success' } })
        expect(w.find('.badge').classes()).toContain('badge-success')
    })
    it('emits "tab" with id when switchHandler is set and clicked', async () => {
        const w = mount(TabHeader, {
            props: { id: 'tab1', title: 'T', switchHandler: true },
        })
        await w.find('a').trigger('click')
        expect(w.emitted('tab')).toBeTruthy()
        expect(w.emitted('tab')[0]).toEqual(['tab1'])
    })
    it('does not emit "tab" when switchHandler is not set', async () => {
        const w = mount(TabHeader, { props: { id: 'tab1', title: 'T' } })
        await w.find('a').trigger('click')
        expect(w.emitted('tab')).toBeUndefined()
    })
})

describe('tabHeaders', () => {
    it('renders .nav.nav-tabs list', () => {
        const w = mount(TabHeaders, { slots: { default: '<li>Tab</li>' } })
        expect(w.classes()).toContain('nav')
        expect(w.classes()).toContain('nav-tabs')
        expect(w.find('li').text()).toBe('Tab')
    })
})

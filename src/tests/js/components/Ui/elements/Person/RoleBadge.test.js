import { mount } from '@vue/test-utils'
import RoleBadge from '@/components/Ui/elements/Person/RoleBadge.vue'

const role = {
    name: 'Administrator',
    permissions: [
        { name: 'view-users' },
        { name: 'edit-users' },
    ],
}

describe('RoleBadge', () => {
    it('shows the role name', () => {
        const w = mount(RoleBadge, { props: { role } })
        expect(w.text()).toBe('Administrator')
    })
    it('has badge styling', () => {
        const w = mount(RoleBadge, { props: { role } })
        expect(w.classes()).toContain('badge')
    })
    it('shows permission names in title', () => {
        const w = mount(RoleBadge, { props: { role } })
        expect(w.attributes('title')).toBe('view-users, edit-users')
    })
    it('handles role with no permissions', () => {
        const w = mount(RoleBadge, { props: { role: { name: 'Gast', permissions: [] } } })
        expect(w.attributes('title')).toBe('')
    })
})

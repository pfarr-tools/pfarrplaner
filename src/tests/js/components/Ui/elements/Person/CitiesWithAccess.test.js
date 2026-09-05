import { mount } from '@vue/test-utils'
import CitiesWithAccess from '@/components/Ui/elements/Person/CitiesWithAccess.vue'

const user = {
    admin_cities: [{ id: 1, name: 'Albstadt' }],
    writable_cities: [{ id: 2, name: 'Balingen' }],
    cities: [{ id: 3, name: 'Sigmaringen' }],
}

const adminPage = { props: { currentUser: { data: { cities: [1, 2, 3], isAdmin: true } } } }
const nonAdminPage = (cityIds) => ({ props: { currentUser: { data: { cities: cityIds, isAdmin: false } } } })

describe('CitiesWithAccess', () => {
    it('shows admin city with bg-admin badge', () => {
        const w = mount(CitiesWithAccess, {
            props: { user },
            global: { mocks: { $page: adminPage } },
        })
        const badge = w.findAll('.badge').find(b => b.text() === 'Albstadt')
        expect(badge?.classes()).toContain('bg-admin')
    })
    it('shows writable city with bg-success badge', () => {
        const w = mount(CitiesWithAccess, {
            props: { user },
            global: { mocks: { $page: adminPage } },
        })
        const badge = w.findAll('.badge').find(b => b.text() === 'Balingen')
        expect(badge?.classes()).toContain('bg-success')
    })
    it('shows read-only city with bg-warning badge', () => {
        const w = mount(CitiesWithAccess, {
            props: { user },
            global: { mocks: { $page: adminPage } },
        })
        const badge = w.findAll('.badge').find(b => b.text() === 'Sigmaringen')
        expect(badge?.classes()).toContain('bg-warning')
    })
    it('shows all cities when isAdmin=true regardless of visible cities', () => {
        const w = mount(CitiesWithAccess, {
            props: { user },
            global: { mocks: { $page: { props: { currentUser: { data: { cities: [], isAdmin: true } } } } } },
        })
        expect(w.findAll('.badge')).toHaveLength(3)
    })
    it('filters to visibleCities when isAdmin=false', () => {
        const w = mount(CitiesWithAccess, {
            props: { user },
            global: { mocks: { $page: nonAdminPage([1]) } },
        })
        const badges = w.findAll('.badge')
        expect(badges).toHaveLength(1)
        expect(badges[0].text()).toBe('Albstadt')
    })
    it('shows no badges when isAdmin=false and no visible cities match', () => {
        const w = mount(CitiesWithAccess, {
            props: { user },
            global: { mocks: { $page: nonAdminPage([]) } },
        })
        expect(w.findAll('.badge')).toHaveLength(0)
    })
})

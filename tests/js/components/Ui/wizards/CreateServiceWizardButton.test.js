import { mount } from '@vue/test-utils'
import CreateServiceWizardButton from '@/components/Ui/wizards/CreateServiceWizardButton.vue'

const singleCity = [{ id: 1, name: 'Albstadt' }]
const multiCities = [{ id: 1, name: 'Albstadt' }, { id: 2, name: 'Balingen' }]

describe('CreateServiceWizardButton', () => {
    it('renders an inertia-link (stubbed as <a>) for a single city', () => {
        const w = mount(CreateServiceWizardButton, {
            props: { cities: singleCity, date: '2024-06-09' },
        })
        expect(w.find('a').exists()).toBe(true)
        expect(w.find('.dropdown').exists()).toBe(false)
    })
    it('renders a dropdown for multiple cities', () => {
        const w = mount(CreateServiceWizardButton, {
            props: { cities: multiCities, date: '2024-06-09' },
        })
        expect(w.find('.dropdown').exists()).toBe(true)
    })
    it('renders one dropdown item per city', () => {
        const w = mount(CreateServiceWizardButton, {
            props: { cities: multiCities, date: '2024-06-09' },
        })
        const items = w.findAll('.dropdown-item')
        expect(items).toHaveLength(2)
        expect(items[0].text()).toBe('Albstadt')
        expect(items[1].text()).toBe('Balingen')
    })
    it('uses custom title when title prop is set', () => {
        const w = mount(CreateServiceWizardButton, {
            props: { cities: singleCity, date: '2024-06-09', title: 'Veranstaltung anlegen' },
        })
        expect(w.text()).toContain('Veranstaltung anlegen')
    })
    it('applies btn-primary class when type=primary', () => {
        const w = mount(CreateServiceWizardButton, {
            props: { cities: singleCity, date: '2024-06-09', type: 'primary' },
        })
        expect(w.find('.btn').classes()).toContain('btn-primary')
    })
    it('createNewEntry calls $inertia.get for service.create without events', () => {
        const inertiaGet = vi.fn()
        const w = mount(CreateServiceWizardButton, {
            props: { cities: multiCities, date: '2024-06-09' },
            global: { mocks: { $inertia: { get: inertiaGet }, route: vi.fn((name) => `/${name}`) } },
        })
        w.vm.createNewEntry(multiCities[0])
        expect(inertiaGet).toHaveBeenCalled()
    })
    it('createNewEntry calls $inertia.get for event.create when events=true', () => {
        const inertiaGet = vi.fn()
        const w = mount(CreateServiceWizardButton, {
            props: { cities: multiCities, date: '2024-06-09', events: true },
            global: { mocks: { $inertia: { get: inertiaGet }, route: vi.fn((name) => `/${name}`) } },
        })
        w.vm.createNewEntry(multiCities[0])
        expect(inertiaGet).toHaveBeenCalled()
    })
    it('formats myDate from date prop as YYYY-MM-DD', () => {
        const w = mount(CreateServiceWizardButton, {
            props: { cities: singleCity, date: '2024-06-09' },
        })
        expect(w.vm.myDate).toBe('2024-06-09')
    })
})

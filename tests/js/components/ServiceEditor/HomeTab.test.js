import { mount } from '@vue/test-utils'
import HomeTab from '@/components/ServiceEditor/tabs/HomeTab.vue'

const location = { id: 5, name: 'Stadtkirche', city: { name: 'Musterstadt' } }

const baseService = () => ({
    id: 1,
    city_id: 1,
    city: { name: 'Musterstadt', konfiapp_apikey: null },
    event_class: 'service',
    date: '2025-01-01T10:00:00Z',
    end: null,
    is_allday: false,
    hidden: false,
    location_id: location.id,
    location,
    special_location: null,
    controlled_access: 0,
    related_cities: [],
    baptism: false,
    eucharist: false,
    title: '',
    description: '',
    internal_remarks: '',
    announcements: '',
    alt_proprium: null,
    tags: [],
    service_groups: [],
    attachments: [],
    konfiapp_event_type: null,
    konfiapp_event_qr: null,
})

const stubs = {
    IconBlock: { template: '<div><slot /></div>' },
    LocationSelect: true,
    FormSelectize: true,
    FormInput: true,
    FormCheck: true,
    FormTextarea: true,
    FormDatePicker: true,
    PropriumSelect: true,
    KonfiAppEventTypeSelect: true,
    TagSelect: true,
    ServiceGroupSelect: true,
    FormGroup: true,
    FormRadioGroup: true,
}

function mountIt(serviceOverrides = {}) {
    return mount(HomeTab, {
        props: {
            service: { ...baseService(), ...serviceOverrides },
            locations: [location],
            tags: [],
            serviceGroups: [],
            cities: [{ id: 1, name: 'Musterstadt' }],
            liturgyInfo: [],
        },
        global: { stubs },
    })
}

describe('HomeTab.setLocation', () => {
    it('clears all location fields when called with null', () => {
        const w = mountIt()
        w.vm.setLocation(null)
        expect(w.vm.myLocation).toBeNull()
        expect(w.vm.myService.location_id).toBeNull()
        expect(w.vm.myService.location).toBeNull()
        expect(w.vm.myService.special_location).toBeNull()
    })

    it('clears all location fields when called with undefined', () => {
        const w = mountIt()
        w.vm.setLocation(undefined)
        expect(w.vm.myLocation).toBeNull()
        expect(w.vm.myService.location_id).toBeNull()
    })

    it('sets location object fields when called with a location object', () => {
        const w = mountIt({ location: null, location_id: null })
        w.vm.setLocation(location)
        expect(w.vm.myLocation).toEqual(location)
        expect(w.vm.myService.location_id).toBe(location.id)
        expect(w.vm.myService.location).toEqual(location)
        expect(w.vm.myService.special_location).toBeNull()
    })

    it('sets special_location when called with a string (freetext)', () => {
        const w = mountIt()
        w.vm.setLocation('Gemeindesaal')
        expect(w.vm.myLocation).toBe('Gemeindesaal')
        expect(w.vm.myService.special_location).toBe('Gemeindesaal')
        expect(w.vm.myService.location_id).toBe(0)
        expect(w.vm.myService.location).toBeNull()
    })

    it('does not leave locationUpdating true after a null clear', () => {
        const w = mountIt()
        w.vm.setLocation(null)
        expect(w.vm.locationUpdating).toBe(false)
    })
})

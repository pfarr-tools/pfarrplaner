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
    VueDatePicker: true,
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

describe('HomeTab event date range bridge', () => {
    it('maps the date range to start and end dates for all-day events', () => {
        const w = mountIt({
            event_class: 'event',
            is_allday: true,
            end: '2025-01-01T23:59:59Z',
        })

        w.vm.setEventDateRange([new Date(2025, 1, 10), new Date(2025, 1, 12)])

        expect(moment(w.vm.myService.date).format('YYYY-MM-DD HH:mm:ss')).toBe('2025-02-10 00:00:00')
        expect(moment(w.vm.myService.end).format('YYYY-MM-DD HH:mm:ss')).toBe('2025-02-12 23:59:59')
    })

    it('stores start and end timestamps from the event date range picker', () => {
        const w = mountIt({
            event_class: 'event',
            date: '2025-01-01T09:30:00Z',
            end: '2025-01-03T18:15:00Z',
        })

        w.vm.setEventDateRange([new Date(2025, 1, 10, 9, 30), new Date(2025, 1, 12, 18, 15)])

        expect(moment(w.vm.myService.date).format('YYYY-MM-DD HH:mm:ss')).toBe('2025-02-10 09:30:00')
        expect(moment(w.vm.myService.end).format('YYYY-MM-DD HH:mm:ss')).toBe('2025-02-12 18:15:00')
    })
})

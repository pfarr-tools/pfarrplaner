import { mount } from '@vue/test-utils'
import CityLocationFilter from '@/components/Reports/CityLocationFilter.vue'

const cities = [
    { id: 1, name: 'Musterstadt' },
    { id: 2, name: 'Nebenort' },
]

const locations = [
    { id: 11, name: 'Stadtkirche', city_id: 1, city: { name: 'Musterstadt' } },
    { id: 12, name: 'Gemeindehaus', city_id: 1, city: { name: 'Musterstadt' } },
    { id: 21, name: 'Kapelle', city_id: 2, city: { name: 'Nebenort' } },
]

function mountIt(props = {}) {
    return mount(CityLocationFilter, {
        props: {
            cities,
            locations,
            cityModelValue: [1],
            locationModelValue: [11],
            ...props,
        },
        global: {
            stubs: {
                FormSelectize: { template: '<div />' },
                LocationSelect: { template: '<div />' },
            },
        },
    })
}

describe('CityLocationFilter', () => {
    it('filters locations to the selected cities', () => {
        const w = mountIt({ cityModelValue: [1] })
        expect(w.vm.filteredLocations.map(item => item.id)).toEqual([11, 12])
    })

    it('emits selected city ids as an array', async () => {
        const w = mountIt()
        await w.setData({ myCities: [1, 2] })
        expect(w.emitted('update:cityModelValue')?.at(-1)).toEqual([[1, 2]])
    })

    it('removes locations that no longer belong to the selected cities', async () => {
        const w = mountIt({
            cityModelValue: [1, 2],
            locationModelValue: [11, 21],
        })

        await w.setData({ myCities: [1] })

        expect(w.vm.myLocations).toEqual([11])
        expect(w.emitted('update:locationModelValue')?.at(-1)).toEqual([[11]])
    })

    it('returns all locations empty when no city is selected', async () => {
        const w = mountIt({ cityModelValue: [] })
        await w.setData({ myCities: [] })
        expect(w.vm.filteredLocations).toEqual([])
    })
})

import { mount } from '@vue/test-utils'
import ChildrensChurchSetup from '@/Pages/Inputs/ChildrensChurch/Setup.vue'
import OfferingsSetup from '@/Pages/Inputs/Offerings/Setup.vue'
import PlanningInputSetup from '@/Pages/Inputs/Planning/PlanningInputSetup.vue'

const cities = [{ id: 1, name: 'Gemeinde A' }, { id: 2, name: 'Gemeinde B' }]
const locations = [{ id: 1, name: 'Kirche A' }]
const ministries = { P: 'Pfarrer/in', K: 'Kantor/in' }

const stubs = {
    AdminLayout: { template: '<div><slot /><slot name="navbar-left" /></div>' },
    DateRangeInput: { template: '<div />', props: ['modelValue', 'label'] },
    FormSelectize: { template: '<div />', props: ['label', 'options', 'modelValue', 'multiple', 'name', 'placeholder', 'help'] },
    NavButton: { template: '<button><slot /></button>', props: ['type', 'title', 'icon', 'disabled'] },
    LocationSelect: { template: '<div />' },
}

function mountCC(props = {}) {
    return mount(ChildrensChurchSetup, { props: { cities, locations, ...props }, global: { stubs } })
}
function mountOff(props = {}) {
    return mount(OfferingsSetup, { props: { cities, locations, ...props }, global: { stubs } })
}
function mountPlan(props = {}) {
    return mount(PlanningInputSetup, { props: { cities, locations, ministries, ...props }, global: { stubs } })
}

// — ChildrensChurch Setup —

describe('ChildrensChurch Setup — dateRange computed', () => {
    it('produces a two-element array of dayjs objects from DD.MM.YYYY strings', () => {
        const w = mountCC()
        const [f, t] = w.vm.dateRange
        expect(window.moment.isDayjs(f)).toBe(true)
        expect(window.moment.isDayjs(t)).toBe(true)
    })

    it('parses the correct year from setup.from', () => {
        const w = mountCC()
        w.vm.setup.from = '01.06.2026'
        const [f] = w.vm.dateRange
        expect(f.year()).toBe(2026)
        expect(f.month()).toBe(5)
        expect(f.date()).toBe(1)
    })

    it('returns nulls when from/to are empty', () => {
        const w = mountCC()
        w.vm.setup.from = null
        w.vm.setup.to = null
        const [f, t] = w.vm.dateRange
        expect(f).toBeNull()
        expect(t).toBeNull()
    })
})

describe('ChildrensChurch Setup — onDateRangeChange', () => {
    it('updates setup.from/to as DD.MM.YYYY', async () => {
        const w = mountCC()
        const from = window.moment('2026-03-01')
        const to = window.moment('2026-06-30')
        await w.vm.onDateRangeChange([from, to])
        expect(w.vm.setup.from).toBe('01.03.2026')
        expect(w.vm.setup.to).toBe('30.06.2026')
    })

    it('does not update when second date is missing', async () => {
        const w = mountCC()
        const original = w.vm.setup.from
        await w.vm.onDateRangeChange([window.moment('2026-03-01')])
        expect(w.vm.setup.from).toBe(original)
        await w.vm.onDateRangeChange(null)
        expect(w.vm.setup.from).toBe(original)
    })
})

// — Offerings Setup —

describe('Offerings Setup — dateRange computed', () => {
    it('produces a two-element array of dayjs objects', () => {
        const w = mountOff()
        const [f, t] = w.vm.dateRange
        expect(window.moment.isDayjs(f)).toBe(true)
        expect(window.moment.isDayjs(t)).toBe(true)
    })

    it('defaults to start-of-year / end-of-year', () => {
        const w = mountOff()
        const [f, t] = w.vm.dateRange
        expect(f.month()).toBe(0)
        expect(f.date()).toBe(1)
        expect(t.month()).toBe(11)
        expect(t.date()).toBe(31)
    })
})

describe('Offerings Setup — onDateRangeChange', () => {
    it('updates setup.from/to as DD.MM.YYYY', async () => {
        const w = mountOff()
        await w.vm.onDateRangeChange([window.moment('2026-04-01'), window.moment('2026-09-30')])
        expect(w.vm.setup.from).toBe('01.04.2026')
        expect(w.vm.setup.to).toBe('30.09.2026')
    })

    it('does not update on incomplete range', async () => {
        const w = mountOff()
        const original = w.vm.setup.from
        await w.vm.onDateRangeChange(null)
        expect(w.vm.setup.from).toBe(original)
    })
})

// — Planning Setup —

describe('PlanningInputSetup — dateRange computed', () => {
    it('produces a two-element array of dayjs objects', () => {
        const w = mountPlan()
        const [f, t] = w.vm.dateRange
        expect(window.moment.isDayjs(f)).toBe(true)
        expect(window.moment.isDayjs(t)).toBe(true)
    })
})

describe('PlanningInputSetup — onDateRangeChange', () => {
    it('updates setup.from/to as DD.MM.YYYY', async () => {
        const w = mountPlan()
        await w.vm.onDateRangeChange([window.moment('2026-05-01'), window.moment('2026-05-31')])
        expect(w.vm.setup.from).toBe('01.05.2026')
        expect(w.vm.setup.to).toBe('31.05.2026')
    })
})

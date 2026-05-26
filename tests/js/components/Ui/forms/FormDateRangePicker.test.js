import { mount } from '@vue/test-utils'
import FormDateRangePicker from '@/components/Ui/forms/FormDateRangePicker.vue'

const VueDatePickerStub = {
    name: 'VueDatePicker',
    template: '<div class="dp-stub"></div>',
    props: ['modelValue'],
    emits: ['update:modelValue'],
}

const stubs = {
    VueDatePicker: VueDatePickerStub,
    FormGroup: { template: '<div><slot /></div>' },
}

const jan1 = '2025-01-01'
const dec31 = '2025-12-31'

function mountPicker(props = {}) {
    return mount(FormDateRangePicker, { props, global: { stubs } })
}

describe('FormDateRangePicker — hidden inputs', () => {
    it('renders two hidden inputs with default names start/end', () => {
        const w = mountPicker({ from: jan1, to: dec31 })
        const inputs = w.findAll('input[type="hidden"]')
        expect(inputs).toHaveLength(2)
        expect(inputs[0].attributes('name')).toBe('start')
        expect(inputs[1].attributes('name')).toBe('end')
    })

    it('uses custom nameFrom / nameTo props', () => {
        const w = mountPicker({ from: jan1, to: dec31, nameFrom: 'baptismDatesStart', nameTo: 'baptismDatesEnd' })
        const inputs = w.findAll('input[type="hidden"]')
        expect(inputs[0].attributes('name')).toBe('baptismDatesStart')
        expect(inputs[1].attributes('name')).toBe('baptismDatesEnd')
    })

    it('formats hidden input values as DD.MM.YYYY', () => {
        const w = mountPicker({ from: jan1, to: dec31 })
        const inputs = w.findAll('input[type="hidden"]')
        expect(inputs[0].element.value).toBe('01.01.2025')
        expect(inputs[1].element.value).toBe('31.12.2025')
    })

    it('hidden inputs show empty string when from/to are null', () => {
        const w = mountPicker({})
        const inputs = w.findAll('input[type="hidden"]')
        expect(inputs[0].element.value).toBe('')
        expect(inputs[1].element.value).toBe('')
    })

    it('formats hidden values from ISO string input', () => {
        // Use midday to avoid local-timezone boundary shifts
        const w = mountPicker({ from: '2025-03-15T12:00:00.000Z', to: '2025-06-30T12:00:00.000Z' })
        expect(w.findAll('input[type="hidden"]')[0].element.value).toBe('15.03.2025')
        expect(w.findAll('input[type="hidden"]')[1].element.value).toBe('30.06.2025')
    })
})

describe('FormDateRangePicker — onRangeChange with iso-date', () => {
    it('emits ISO strings for update:from and update:to', async () => {
        const w = mountPicker({ from: jan1, to: dec31, isoDate: true })
        const from = new Date(Date.UTC(2025, 2, 1))
        const to = new Date(Date.UTC(2025, 8, 30))
        await w.vm.onRangeChange([from, to])
        expect(w.emitted('update:from')).toBeTruthy()
        expect(w.emitted('update:to')).toBeTruthy()
        expect(w.emitted('update:from')[0][0]).toBe('2025-03-01')
        expect(w.emitted('update:to')[0][0]).toBe('2025-09-30')
    })
})

describe('FormDateRangePicker — onRangeChange without iso-date', () => {
    it('emits DD.MM.YYYY strings for update:from and update:to', async () => {
        const w = mountPicker({ from: jan1, to: dec31, isoDate: false })
        const from = new Date(Date.UTC(2025, 3, 1))
        const to = new Date(Date.UTC(2025, 3, 30))
        await w.vm.onRangeChange([from, to])
        expect(w.emitted('update:from')[0][0]).toBe('01.04.2025')
        expect(w.emitted('update:to')[0][0]).toBe('30.04.2025')
    })

    it('does not emit when second date is missing', async () => {
        const w = mountPicker({ from: jan1, to: dec31 })
        await w.vm.onRangeChange([new Date('2025-04-01')])
        expect(w.emitted('update:from')).toBeFalsy()
        await w.vm.onRangeChange(null)
        expect(w.emitted('update:from')).toEqual([[null]])
        expect(w.emitted('update:to')).toEqual([[null]])
    })
})

describe('FormDateRangePicker — formatting helpers', () => {
    it('formats values for display as DD.MM.YYYY', () => {
        const w = mountPicker({})
        expect(w.vm.formatDisplayDate('2025-01-01')).toBe('01.01.2025')
        expect(w.vm.formatDisplayDate(new Date(Date.UTC(2025, 11, 31)))).toBe('31.12.2025')
    })

    it('formats values for iso-date submission as YYYY-MM-DD', () => {
        const w = mountPicker({})
        expect(w.vm.formatIsoDate('01.01.2025')).toBe('2025-01-01')
        expect(w.vm.formatIsoDate(new Date(Date.UTC(2025, 11, 31)))).toBe('2025-12-31')
    })

    it('returns empty string for unsupported or missing values', () => {
        const w = mountPicker({})
        expect(w.vm.formatDisplayDate(null)).toBe('')
        expect(w.vm.formatIsoDate({})).toBe('')
    })
})

describe('FormDateRangePicker — pickerRange sync', () => {
    it('converts from/to props to UTC-midnight Date objects', () => {
        const w = mountPicker({ from: jan1, to: dec31 })
        const [f, t] = w.vm.pickerRange
        expect(f).toBeInstanceOf(Date)
        expect(t).toBeInstanceOf(Date)
        expect(f.getUTCFullYear()).toBe(2025)
        expect(f.getUTCMonth()).toBe(0)
        expect(f.getUTCDate()).toBe(1)
        expect(t.getUTCFullYear()).toBe(2025)
        expect(t.getUTCMonth()).toBe(11)
        expect(t.getUTCDate()).toBe(31)
    })

    it('returns [null, null] when both props are absent', () => {
        const w = mountPicker({})
        expect(w.vm.pickerRange).toEqual([null, null])
    })

    it('normalizes ISO datetime strings to their UTC calendar day', () => {
        const w = mountPicker({ from: '2025-12-31T22:00:00.000Z', to: '2025-12-31T22:00:00.000Z' })
        const [f] = w.vm.pickerRange
        expect(f).toBeInstanceOf(Date)
        expect(f.getUTCFullYear()).toBe(2025)
        expect(f.getUTCMonth()).toBe(11)
        expect(f.getUTCDate()).toBe(31)
        expect(f.getUTCHours()).toBe(0)
        expect(f.getUTCMinutes()).toBe(0)
    })
})

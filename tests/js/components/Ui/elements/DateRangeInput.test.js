import { mount } from '@vue/test-utils'
import DateRangeInput from '@/components/Ui/elements/DateRangeInput.vue'

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

const jan1 = window.moment('2025-01-01')
const dec31 = window.moment('2025-12-31')

function mountInput(props = {}) {
    return mount(DateRangeInput, { props, global: { stubs } })
}

describe('DateRangeInput — internalRange from modelValue', () => {
    it('converts modelValue [moment, moment] to UTC-midnight Date objects', () => {
        const w = mountInput({ modelValue: [jan1, dec31] })
        const [f, t] = w.vm.internalRange
        expect(f).toBeInstanceOf(Date)
        expect(t).toBeInstanceOf(Date)
        // UTC date must equal the local calendar date (no DST shift)
        expect(f.getUTCFullYear()).toBe(2025)
        expect(f.getUTCMonth()).toBe(0)
        expect(f.getUTCDate()).toBe(1)
        expect(f.getUTCHours()).toBe(0)
        expect(t.getUTCDate()).toBe(31)
        expect(t.getUTCMonth()).toBe(11)
    })

    it('returns null when modelValue is empty', () => {
        const w = mountInput({ modelValue: [] })
        expect(w.vm.internalRange).toBeNull()
    })

    it('returns null when modelValue is null', () => {
        const w = mountInput({})
        expect(w.vm.internalRange).toBeNull()
    })
})

describe('DateRangeInput — internalRange from from/to props', () => {
    it('falls back to from/to props when modelValue is absent', () => {
        const w = mountInput({ from: jan1, to: dec31 })
        const [f, t] = w.vm.internalRange
        expect(f).toBeInstanceOf(Date)
        expect(t).toBeInstanceOf(Date)
    })

    it('prefers modelValue over from/to props', () => {
        const other = window.moment('2024-06-15')
        const w = mountInput({ modelValue: [jan1, dec31], from: other, to: other })
        const [f] = w.vm.internalRange
        // Should use modelValue[0] (jan1), not from (other)
        expect(window.moment(f).format('YYYY')).toBe('2025')
    })
})

describe('DateRangeInput — onRangeChange', () => {
    it('emits update:modelValue with [moment, moment]', async () => {
        const w = mountInput({ modelValue: [jan1, dec31] })
        const from = new Date('2025-03-01T00:00:00')
        const to = new Date('2025-09-30T00:00:00')
        await w.vm.onRangeChange([from, to])
        expect(w.emitted('update:modelValue')).toBeTruthy()
        const [start, end] = w.emitted('update:modelValue')[0][0]
        expect(window.moment.isDayjs(start) || start != null).toBe(true)
    })

    it('also emits input event', async () => {
        const w = mountInput({ modelValue: [jan1, dec31] })
        await w.vm.onRangeChange([new Date('2025-03-01'), new Date('2025-09-30')])
        expect(w.emitted('input')).toBeTruthy()
    })

    it('does not emit when second date is missing', async () => {
        const w = mountInput({ modelValue: [jan1, dec31] })
        await w.vm.onRangeChange([new Date('2025-03-01')])
        expect(w.emitted('update:modelValue')).toBeFalsy()
        await w.vm.onRangeChange(null)
        expect(w.emitted('update:modelValue')).toBeFalsy()
    })
})

describe('DateRangeInput — formatDisplay', () => {
    it('returns "DD.MM.YYYY – DD.MM.YYYY" for a full range', () => {
        const w = mountInput({})
        const result = w.vm.formatDisplay([new Date('2025-01-01'), new Date('2025-12-31')])
        expect(result).toBe('01.01.2025 – 31.12.2025')
    })

    it('returns only start date when end is absent', () => {
        const w = mountInput({})
        expect(w.vm.formatDisplay([new Date('2025-01-01')])).toBe('01.01.2025')
    })

    it('returns empty string for null input', () => {
        const w = mountInput({})
        expect(w.vm.formatDisplay(null)).toBe('')
    })
})

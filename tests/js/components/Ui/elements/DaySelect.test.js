import { mount } from '@vue/test-utils'
import DaySelect from '@/components/Ui/elements/DaySelect.vue'

const days = [
    { id: 1, date: '2024-01-07' },
    { id: 2, date: '2024-01-14' },
]

describe('DaySelect', () => {
    it('renders one option per day', () => {
        const w = mount(DaySelect, { props: { name: 'day', days, modelValue: null } })
        expect(w.findAll('option')).toHaveLength(2)
    })
    it('formats dates in DD.MM.YYYY', () => {
        const w = mount(DaySelect, { props: { name: 'day', days, modelValue: null } })
        expect(w.text()).toContain('07.01.2024')
    })
    it('pre-selects the matching day id from modelValue', () => {
        const w = mount(DaySelect, { props: { name: 'day', days, modelValue: days[1] } })
        expect(w.vm.myValue).toBe(2)
    })
    it('emits update:modelValue with day object on input', async () => {
        const w = mount(DaySelect, { props: { name: 'day', days, modelValue: null } })
        const select = w.find('select')
        select.element.value = '1'
        await select.trigger('input')
        expect(w.emitted('update:modelValue')?.[0]?.[0]).toEqual(days[0])
    })
    it('emits input with day object on input', async () => {
        const w = mount(DaySelect, { props: { name: 'day', days, modelValue: null } })
        const select = w.find('select')
        select.element.value = '2'
        await select.trigger('input')
        expect(w.emitted('input')?.[0]?.[0]).toEqual(days[1])
    })
    it('applies is-invalid class when error prop is set', () => {
        const w = mount(DaySelect, { props: { name: 'day', days, modelValue: null, error: 'Pflicht' } })
        expect(w.find('select').classes()).toContain('is-invalid')
    })
})

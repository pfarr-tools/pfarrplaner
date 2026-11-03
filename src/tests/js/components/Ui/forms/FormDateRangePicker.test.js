import { mount } from '@vue/test-utils'
import FormDateRangePicker from '@/components/Ui/forms/FormDateRangePicker.vue'

const stubs = {
    FormGroup: { template: '<div><slot /></div>' },
    VueDatePicker: { template: '<div />' },
}

describe('FormDateRangePicker', () => {
    it('keeps a Berlin midnight on the selected calendar day', async () => {
        const wrapper = mount(FormDateRangePicker, { global: { stubs } })
        const berlinMidnight = new Date('2026-12-31T23:00:00.000Z')

        expect(wrapper.vm.formatDisplayDate(berlinMidnight)).toBe('01.01.2027')
        expect(wrapper.vm.formatIsoDate(berlinMidnight)).toBe('2027-01-01')

        await wrapper.vm.onRangeChange([berlinMidnight, berlinMidnight])

        expect(wrapper.emitted('update:from')[0]).toEqual(['01.01.2027'])
        expect(wrapper.emitted('update:to')[0]).toEqual(['01.01.2027'])
    })
})

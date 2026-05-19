import { mount } from '@vue/test-utils'
import DatePickerShim from '@/components/Ui/forms/DatePickerShim.vue'

const VueDatePickerStub = {
    name: 'VueDatePicker',
    template: '<div class="dp-stub"></div>',
    props: ['modelValue'],
    emits: ['update:modelValue'],
}

function mountPicker(props = {}) {
    return mount(DatePickerShim, {
        props,
        global: {
            stubs: {
                VueDatePicker: VueDatePickerStub,
            },
        },
    })
}

describe('DatePickerShim with isoDate', () => {
    it('shows ISO datetimes in Europe/Berlin wall-clock time', () => {
        const w = mountPicker({
            isoDate: true,
            modelValue: '2025-01-01T10:00:00.000Z',
            config: { locale: 'de', format: 'DD.MM.YYYY HH:mm' },
        })

        expect(w.vm.internalDate.getFullYear()).toBe(2025)
        expect(w.vm.internalDate.getMonth()).toBe(0)
        expect(w.vm.internalDate.getDate()).toBe(1)
        expect(w.vm.internalDate.getHours()).toBe(11)
        expect(w.vm.internalDate.getMinutes()).toBe(0)
    })

    it('emits a Berlin-local formatted value and UTC ISO input when the user changes the time', async () => {
        const w = mountPicker({
            isoDate: true,
            config: { locale: 'de', format: 'DD.MM.YYYY HH:mm' },
        })

        await w.vm.onDateChange(new Date(2025, 0, 1, 11, 0, 0))

        expect(w.emitted('update:modelValue')[0][0]).toBe('01.01.2025 11:00')
        expect(w.emitted('input')[0][0]).toBe('2025-01-01T10:00:00.000Z')
    })
})

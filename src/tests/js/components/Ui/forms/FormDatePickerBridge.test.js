import { mount } from '@vue/test-utils'
import FormDatePicker from '@/components/Ui/forms/FormDatePicker.vue'

const DatePickerStub = {
    name: 'date-picker',
    template: '<div class="date-picker-stub"></div>',
    props: ['modelValue', 'config'],
    emits: ['update:modelValue', 'input', 'dp-update'],
}

function mountPicker(props = {}) {
    return mount(FormDatePicker, {
        props,
        global: {
            stubs: {
                FormGroup: { template: '<div><slot fieldId="testField" describedBy="" error="" /></div>' },
                'date-picker': DatePickerStub,
            },
        },
    })
}

describe('FormDatePicker isoDate bridge', () => {
    it('stores Berlin local datetime values as UTC ISO strings in v-model', () => {
        const w = mountPicker({
            isoDate: true,
            config: { locale: 'de', format: 'DD.MM.YYYY HH:mm' },
        })

        w.vm.handleModelUpdate('01.01.2025 08:00')

        expect(w.emitted('update:modelValue')[0][0]).toBe('2025-01-01T07:00:00.000Z')
    })

    it('emits the same UTC ISO string for input events', () => {
        const w = mountPicker({
            isoDate: true,
            config: { locale: 'de', format: 'DD.MM.YYYY HH:mm' },
        })

        w.vm.handleInputEvent('01.01.2025 08:00')

        expect(w.emitted('input')[0][0]).toBe('2025-01-01T07:00:00.000Z')
    })

    it('reinterprets ISO-like values as Berlin wall-clock time before submitting', () => {
        const w = mountPicker({
            isoDate: true,
            config: { locale: 'de', format: 'DD.MM.YYYY HH:mm' },
        })

        w.vm.handleModelUpdate('2026-07-29T08:00:00.000000Z')

        expect(w.emitted('update:modelValue')[0][0]).toBe('2026-07-29T06:00:00.000Z')
    })
})

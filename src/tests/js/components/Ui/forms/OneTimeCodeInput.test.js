import { mount } from '@vue/test-utils'
import { defineComponent, ref } from 'vue'
import { describe, expect, it } from 'vitest'
import OneTimeCodeInput from '@/components/Ui/forms/OneTimeCodeInput.vue'

/** Mount the code field inside a v-model host to test parent synchronization. */
const mountCodeInput = () =>
    mount(
        defineComponent({
            components: { OneTimeCodeInput },
            setup() {
                return { code: ref('') }
            },
            template:
                '<OneTimeCodeInput v-model="code" label="Sicherheitscode" test-id-prefix="test-code" />',
        }),
    )

describe('OneTimeCodeInput', () => {
    it('keeps digit positions stable when a middle digit is cleared and replaced', async () => {
        const wrapper = mountCodeInput()
        const inputs = wrapper.findAll('[data-testid^="test-code-digit-"]')

        for (const [index, digit] of '123456'.split('').entries()) {
            await inputs[index].setValue(digit)
        }
        await inputs[2].setValue('')

        expect(inputs.map((input) => input.element.value)).toEqual(['1', '2', '', '4', '5', '6'])
        expect(wrapper.vm.code).toBe('12456')

        await inputs[2].setValue('3')
        expect(inputs.map((input) => input.element.value)).toEqual(['1', '2', '3', '4', '5', '6'])
        expect(wrapper.vm.code).toBe('123456')
    })

    it('announces how many digits were inserted when pasting a code', async () => {
        const wrapper = mountCodeInput()
        await wrapper.find('[data-testid="test-code-digit-0"]').trigger('paste', {
            clipboardData: { getData: () => '12-34 56' },
        })

        expect(wrapper.vm.code).toBe('123456')
        expect(wrapper.find('[aria-live="polite"]').text()).toContain('vollständig')
    })
})

import { mount } from '@vue/test-utils'
import FormBibleReferenceInput from '@/components/Ui/forms/FormBibleReferenceInput.vue'

/** Defines tests for preserving user-entered Bible references. */
function defineBibleReferenceInputTests() {
    /** Mounts the input with a minimal Bible-version page context. */
    function mountReferenceInput() {
        return mount(FormBibleReferenceInput, {
            props: { modelValue: '', noVersion: true },
            global: {
                mocks: {
                    $page: { props: { bible: { versions: ['LUT'] }, errors: {} } },
                },
            },
        })
    }

    /** Keeps typed characters and a trailing book-name space stable across v-model updates. */
    async function preservesTypedReferenceCharacters() {
        const wrapper = mountReferenceInput()
        const input = wrapper.find('input[type="text"]')

        for (const value of ['J', 'Jo', 'Joh', 'Joh ']) {
            await input.setValue(value)
            const emittedValue = wrapper.emitted('update:modelValue').at(-1)[0]
            await wrapper.setProps({ modelValue: emittedValue })

            expect(input.element.value).toBe(value)
        }

        expect(wrapper.emitted('update:modelValue').at(-1)[0]).toBe('Joh  [LUT]')
    }

    it('preserves input spacing while typing', preservesTypedReferenceCharacters)
}

describe('FormBibleReferenceInput', defineBibleReferenceInputTests)

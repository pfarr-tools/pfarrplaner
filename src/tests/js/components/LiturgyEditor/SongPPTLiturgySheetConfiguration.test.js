import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import FormRadioGroup from '@/components/Ui/forms/FormRadioGroup.vue'
import SongPPTLiturgySheetConfiguration from '@/components/LiturgyEditor/LiturgySheets/SongPPTLiturgySheetConfiguration.vue'

/** Defines the output-format selection test suite. */
function defineOutputFormatTests() {
    /** Verifies the available export formats are rendered as radio options. */
    function showsRadioOptions() {
        const wrapper = mount(SongPPTLiturgySheetConfiguration, {
            props: {
                service: { id: 1, slug: 'test', city_id: 2, liturgy_blocks: [] },
                sheet: { key: 'songs', config: {} },
            },
            global: {
                components: { FormRadioGroup },
                mocks: {
                    $api: vi.fn().mockReturnValue({
                        get: vi.fn().mockResolvedValue({ data: [] }),
                    }),
                    $page: { props: { errors: {} } },
                    route: vi.fn().mockReturnValue('/test'),
                },
                stubs: {
                    AttachmentList: true,
                    FormCheck: true,
                    FormFileUploader: true,
                    FormInput: true,
                    FormSelectize: true,
                },
            },
        })

        const outputOptions = wrapper.findAll('input[type="radio"][name="config[outputFormat]"]')

        expect(outputOptions.map((option) => option.element.value)).toEqual(['ppt', 'odp'])
        expect(outputOptions[0].element.checked).toBe(true)
    }

    it('offers output formats as radio options', showsRadioOptions)
}

describe('SongPPTLiturgySheetConfiguration', defineOutputFormatTests)

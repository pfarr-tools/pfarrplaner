import { mount } from '@vue/test-utils'
import TagSelect from '@/components/Ui/elements/TagSelect.vue'

const tags = [
    { id: 1, name: 'Familiengottesdienst' },
    { id: 2, name: 'Abendmahl' },
]

const stubs = {
    FormSelectize: { template: '<div />' },
}

describe('TagSelect', () => {
    it('emits the selected tag ids when the select returns numeric values', () => {
        const w = mount(TagSelect, {
            props: { name: 'tags', tags, modelValue: [], return: 'id' },
            global: { stubs },
        })

        w.vm.handleInput([2])

        expect(w.emitted('update:modelValue')?.[0]).toEqual([[2]])
    })
})

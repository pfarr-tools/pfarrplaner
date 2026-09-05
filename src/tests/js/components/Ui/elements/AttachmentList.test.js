import { mount, flushPromises } from '@vue/test-utils'
import AttachmentList from '@/components/Ui/elements/AttachmentList.vue'

const makeAtt = (id) => ({
    id, title: `File ${id}`, extension: 'pdf', size: 1024,
    icon: 'fa-file', mimeType: 'application/pdf', file: `attachments/file${id}.pdf`,
})

describe('AttachmentList', () => {
    it('renders one item per attachment', () => {
        const w = mount(AttachmentList, { props: { modelValue: [makeAtt(1), makeAtt(2)] } })
        expect(w.text()).toContain('File 1')
        expect(w.text()).toContain('File 2')
    })
    it('shows default empty message when no attachments', () => {
        const w = mount(AttachmentList, { props: { modelValue: [] } })
        expect(w.find('.alert-info').exists()).toBe(true)
        expect(w.text()).toContain('keine Dateianhänge')
    })
    it('shows custom empty message', () => {
        const w = mount(AttachmentList, { props: { modelValue: [], emptyMessage: 'Nichts da!' } })
        expect(w.text()).toContain('Nichts da!')
    })
    it('hides empty message when preventEmptyListMessage is set', () => {
        const w = mount(AttachmentList, { props: { modelValue: [], preventEmptyListMessage: true } })
        expect(w.find('.alert-info').exists()).toBe(false)
    })
    it('calls axios.delete when deleteAttachment is invoked and emits update:modelValue', async () => {
        window.axios.delete.mockResolvedValue({ data: [] })
        const w = mount(AttachmentList, {
            props: {
                modelValue: [makeAtt(1)],
                deleteRouteName: 'attachment.delete',
                parentType: 'service',
                parentObject: { id: 10 },
            },
        })
        w.vm.deleteAttachment(makeAtt(1))
        await flushPromises()
        expect(window.axios.delete).toHaveBeenCalled()
        expect(w.emitted('update:modelValue')?.[0]).toEqual([[]])
    })
    it('does not delete attachment when confirmation is declined', async () => {
        window.confirm.mockReturnValue(false)
        const w = mount(AttachmentList, {
            props: {
                modelValue: [makeAtt(1)],
                deleteRouteName: 'attachment.delete',
                parentType: 'service',
                parentObject: { id: 10 },
            },
        })

        w.vm.deleteAttachment(makeAtt(1))
        await flushPromises()

        expect(window.axios.delete).not.toHaveBeenCalled()
        expect(w.emitted('update:modelValue')).toBeUndefined()
    })
})

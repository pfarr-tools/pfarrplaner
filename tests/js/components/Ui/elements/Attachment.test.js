import { mount } from '@vue/test-utils'
import Attachment from '@/components/Ui/elements/Attachment.vue'

const att = {
    id: 1, title: 'Test.pdf', extension: 'pdf', size: 1024,
    icon: 'fa-file-pdf', mimeType: 'application/pdf', file: 'attachments/test.pdf',
}
const imgAtt = {
    id: 2, title: 'Photo.jpg', extension: 'jpg', size: 2048,
    icon: 'fa-file-image', mimeType: 'image/jpeg', file: 'attachments/photo.jpg',
}

describe('Attachment', () => {
    it('renders title', () => {
        const w = mount(Attachment, { props: { attachment: att } })
        expect(w.text()).toContain('Test.pdf')
    })
    it('renders extension', () => {
        const w = mount(Attachment, { props: { attachment: att } })
        expect(w.text()).toContain('.pdf')
    })
    it('shows delete button when allowDelete=true', () => {
        const w = mount(Attachment, { props: { attachment: att, allowDelete: true } })
        expect(w.find('button[title="Anhang löschen"]').exists()).toBe(true)
    })
    it('hides delete button when allowDelete=false', () => {
        const w = mount(Attachment, { props: { attachment: att, allowDelete: false } })
        expect(w.find('button[title="Anhang löschen"]').exists()).toBe(false)
    })
    it('emits delete-attachment when delete button clicked', async () => {
        const w = mount(Attachment, { props: { attachment: att, allowDelete: true } })
        await w.find('button[title="Anhang löschen"]').trigger('click')
        expect(w.emitted('delete-attachment')).toHaveLength(1)
    })
    it('shows preview img for image mimeType', () => {
        const w = mount(Attachment, { props: { attachment: imgAtt } })
        expect(w.find('img.preview').exists()).toBe(true)
    })
    it('does not show preview img for non-image mimeType', () => {
        const w = mount(Attachment, { props: { attachment: att } })
        expect(w.find('img.preview').exists()).toBe(false)
    })
    it('opens lightbox when preview image clicked', async () => {
        const w = mount(Attachment, { props: { attachment: imgAtt } })
        await w.find('img.preview').trigger('click')
        expect(w.find('.lightbox-backdrop').exists()).toBe(true)
    })
    it('closes lightbox when backdrop clicked', async () => {
        const w = mount(Attachment, { props: { attachment: imgAtt } })
        await w.find('img.preview').trigger('click')
        await w.find('.lightbox-backdrop').trigger('click')
        expect(w.find('.lightbox-backdrop').exists()).toBe(false)
    })
    it('fileSize formats 1024 bytes as 1 kB', () => {
        const w = mount(Attachment, { props: { attachment: att } })
        expect(w.vm.fileSize(1024)).toBe('1 kB')
    })
    it('download calls route with attachment id', async () => {
        const w = mount(Attachment, { props: { attachment: att } })
        const redirectTo = vi.spyOn(w.vm, 'redirectTo').mockImplementation(() => {})
        await w.trigger('click')
        expect(window.route).toHaveBeenCalledWith('attachment', { attachment: 1 })
        expect(redirectTo).toHaveBeenCalledWith('/attachment')
    })
})

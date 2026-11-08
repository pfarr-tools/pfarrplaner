import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AttachmentLightbox from '@/components/Ui/elements/AttachmentLightbox.vue'

const image = (id) => ({ id, title: `Image ${id}`, file: `attachments/image${id}.jpg`, mimeType: 'image/jpeg', hasFile: true })

describe('AttachmentLightbox', () => {
    it('navigates through images and closes on escape', async () => {
        const wrapper = mount(AttachmentLightbox, {
            props: { attachments: [image(1), image(2)], index: 0 },
        })

        await wrapper.find('[aria-label="Nächstes Bild"]').trigger('click')
        expect(wrapper.emitted('next')).toEqual([[]])
        expect(wrapper.find('[aria-label="Vorheriges Bild"]').classes()).toContain('lightbox-prev')

        await wrapper.find('.lightbox-image-stage').element.dispatchEvent(new WheelEvent('wheel', {ctrlKey: true, deltaY: -100}))
        await wrapper.vm.$nextTick()
        expect(wrapper.vm.zoom).toBe(1.25)

        await wrapper.trigger('keydown.left')
        expect(wrapper.emitted('previous')).toEqual([[]])

        await wrapper.trigger('keydown.esc')
        expect(wrapper.emitted('close')).toEqual([[]])
    })
})

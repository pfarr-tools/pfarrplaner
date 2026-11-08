import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import FormFileUpload from '@/components/Ui/forms/FormFileUpload.vue'

const makeFile = (name) => new File(['data'], name, { type: 'image/jpeg' })

describe('FormFileUpload', () => {
    it('emits every selected file once and resets the input value', async () => {
        const wrapper = mount(FormFileUpload, {
            props: { name: 'attachments', multiple: true, noInbox: true },
            global: { mocks: { $api: () => ({ get: () => Promise.resolve({ data: false }) }) } },
        })
        const input = wrapper.find('input[type="file"]')
        const first = makeFile('first.jpg')
        const second = makeFile('second.jpg')
        Object.defineProperty(input.element, 'files', { value: [first, second] })

        await input.trigger('change')

        expect(wrapper.emitted('input')).toEqual([[first], [second]])
        expect(input.element.value).toBe('')
    })

    it('accepts files dropped on the complete upload surface', async () => {
        const wrapper = mount(FormFileUpload, {
            props: { name: 'attachments', multiple: true, noInbox: true },
            global: { mocks: { $api: () => ({ get: () => Promise.resolve({ data: false }) }) } },
        })
        const first = makeFile('dropped.jpg')
        const dropTarget = wrapper.find('.drop-target')

        await dropTarget.trigger('drop', { dataTransfer: { files: [first] } })

        expect(wrapper.emitted('input')).toEqual([[first]])
    })

    it('passes a usable Pixabay image URL to the shared upload flow', () => {
        const wrapper = mount(FormFileUpload, {
            props: { noInbox: true },
            global: { mocks: { $api: () => ({ get: () => Promise.resolve({ data: false }) }) } },
        })

        wrapper.vm.uploadFromPixabay({ largeImageURL: 'https://cdn.example.test/image.jpg' })

        const [payload] = wrapper.emitted('upload-url')[0]
        expect(payload.url).toBe('https://cdn.example.test/image.jpg')
        expect(payload.pixabay).toBe(true)
        expect(payload.info).toEqual({largeImageURL: 'https://cdn.example.test/image.jpg'})
    })

    it('prefers a CDN image over rate-limited Pixabay get URLs', () => {
        const wrapper = mount(FormFileUpload, {
            props: { noInbox: true },
            global: { mocks: { $api: () => ({ get: () => Promise.resolve({ data: false }) }) } },
        })

        wrapper.vm.uploadFromPixabay({
            fullHDURL: 'https://pixabay.com/get/full.jpg',
            webformatURL: 'https://pixabay.com/get/web.jpg',
            previewURL: 'https://cdn.pixabay.com/photo/preview.jpg',
        })

        const [payload] = wrapper.emitted('upload-url')[0]
        expect(payload.url).toBe('https://cdn.pixabay.com/photo/preview.jpg')
    })

    it('upgrades a Pixabay CDN preview to the 1280 pixel variant', () => {
        const wrapper = mount(FormFileUpload, {
            props: { noInbox: true },
            global: { mocks: { $api: () => ({ get: () => Promise.resolve({ data: false }) }) } },
        })

        wrapper.vm.uploadFromPixabay({ previewURL: 'https://cdn.pixabay.com/photo/preview_150.jpg' })

        const [payload] = wrapper.emitted('upload-url')[0]
        expect(payload.url).toBe('https://cdn.pixabay.com/photo/preview_1280.jpg')
    })
})

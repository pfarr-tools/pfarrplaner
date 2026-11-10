import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FormFileUploader from '@/components/Ui/forms/FormFileUploader.vue'

const makeFile = (name = 'Foto.jpg') => new File(['data'], name, { type: 'image/jpeg' })

const mountUploader = () =>
    mount(FormFileUploader, {
        props: { uploadRoute: '/attachments' },
        global: {
            stubs: {
                'form-file-upload': { template: '<div />' },
                modal: { template: '<div><slot /></div>' },
                cropper: { template: '<div />' },
            },
            mocks: { $api: () => window.axios },
        },
    })

describe('FormFileUploader', () => {
    beforeEach(() => {
        window.axios.post.mockResolvedValue({ data: [{ id: 1, title: 'Foto' }] })
    })

    it('queues a selected file and uploads it exactly once', async () => {
        const wrapper = mountUploader()

        wrapper.vm.addFile(makeFile())
        wrapper.vm.applyFileDetails()
        await wrapper.vm.uploadAll()

        expect(window.axios.post).toHaveBeenCalledTimes(1)
        expect(wrapper.vm.queue).toHaveLength(0)
        expect(wrapper.emitted('input')).toEqual([[[{ id: 1, title: 'Foto' }]]])
    })

    it('downloads URL sources locally before adding them to the queue', async () => {
        const blob = new Blob(['image'], { type: 'image/jpeg' })
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ blob: () => Promise.resolve(blob) }))
        const wrapper = mountUploader()

        wrapper.vm.uploadUrl({ url: 'https://example.test/photo.jpg' })
        await flushPromises()

        expect(fetch).toHaveBeenCalledWith('https://example.test/photo.jpg')
        expect(wrapper.vm.queue).toHaveLength(1)
        expect(wrapper.vm.queue[0].file).toBeInstanceOf(File)
        expect(wrapper.vm.queue[0].file.type).toBe('image/jpeg')
        wrapper.vm.applyFileDetails()
    })

    it('downloads authenticated proxy sources with the API client', async () => {
        const blob = new Blob(['image'], { type: 'image/jpeg' })
        window.axios.get.mockResolvedValue({ data: blob })
        const wrapper = mountUploader()

        wrapper.vm.uploadUrl({ url: '/api/pixabay/image?url=source', authenticated: true })
        await flushPromises()

        expect(window.axios.get).toHaveBeenCalledWith('/api/pixabay/image?url=source', {
            responseType: 'blob',
        })
        expect(wrapper.vm.queue[0].file.type).toBe('image/jpeg')
        wrapper.vm.applyFileDetails()
    })

    it('downloads Pixabay sources through the authenticated proxy POST', async () => {
        const blob = new Blob(['image'], { type: 'image/jpeg' })
        window.axios.post.mockResolvedValue({ data: blob })
        const wrapper = mountUploader()

        wrapper.vm.uploadUrl({ url: 'https://cdn.pixabay.com/photo.jpg', pixabay: true })
        await flushPromises()

        expect(window.axios.post).toHaveBeenCalledWith(
            '/api.pixabay.image',
            { url: 'https://cdn.pixabay.com/photo.jpg' },
            { responseType: 'blob' },
        )
        expect(wrapper.vm.queue[0].file.type).toBe('image/jpeg')
        wrapper.vm.applyFileDetails()
    })

    it('duplicates a queued source as an independent target variant', () => {
        const wrapper = mountUploader()
        const source = makeFile()

        wrapper.vm.addFile(source)
        wrapper.vm.duplicate(wrapper.vm.queue[0], {
            cut: 'homepage',
            preset: { width: 1200, height: 630 },
        })

        expect(wrapper.vm.queue).toHaveLength(2)
        expect(wrapper.vm.queue[1].sourceId).toBe(wrapper.vm.queue[0].sourceId)
        expect(wrapper.vm.queue[1].id).not.toBe(wrapper.vm.queue[0].id)
        expect(wrapper.vm.queue[1].cut).toBe('homepage')
    })

    it('assigns a configured target preset to a queued item', () => {
        const wrapper = mount(FormFileUploader, {
            props: {
                uploadRoute: '/attachments',
                presets: [{ title: 'Homepage', cut: 'homepage', width: 1200, height: 630 }],
            },
            global: { stubs: { 'form-file-upload': { template: '<div />' }, modal: true } },
        })
        wrapper.vm.addFile(makeFile())

        wrapper.vm.selectPreset(wrapper.vm.queue[0], 'homepage')

        expect(wrapper.vm.queue[0]).toMatchObject({
            cut: 'homepage',
            preset: { title: 'Homepage', cut: 'homepage', width: 1200, height: 630 },
        })
    })

    it('selects the only configured preset for a new file', () => {
        const preset = {
            title: 'Titelfolie (16:9)',
            cut: 'ppt-title-slide',
            width: 1920,
            height: 1080,
        }
        const wrapper = mount(FormFileUploader, {
            props: { uploadRoute: '/attachments', presets: [preset] },
            global: { stubs: { 'form-file-upload': { template: '<div />' }, modal: true } },
        })

        wrapper.vm.addFile(makeFile())

        expect(wrapper.vm.queue[0]).toMatchObject({ cut: 'ppt-title-slide', preset })
    })

    it('turns one crop result into multiple target variants', () => {
        const wrapper = mount(FormFileUploader, {
            props: {
                uploadRoute: '/attachments',
                presets: [
                    { title: 'Breit', cut: 'wide', width: 1200, height: 630 },
                    { title: 'Hoch', cut: 'portrait', width: 1080, height: 1350 },
                ],
            },
            global: { stubs: { 'form-file-upload': { template: '<div />' }, modal: true } },
        })
        wrapper.vm.addFile(makeFile())
        wrapper.vm.openEditor(wrapper.vm.queue[0])

        wrapper.vm.applyEditorResult(
            makeFile('wide.jpg'),
            { rotation: 0, flipX: false, flipY: false },
            { title: 'Breit', cut: 'wide', width: 1200, height: 630 },
            [
                { file: makeFile('wide.jpg'), transform: { rotation: 0 }, preset: { cut: 'wide' } },
                {
                    file: makeFile('portrait.jpg'),
                    transform: { rotation: 12 },
                    preset: { cut: 'portrait' },
                },
            ],
        )

        expect(wrapper.vm.queue).toHaveLength(2)
        expect(wrapper.vm.queue.map((item) => item.cut)).toEqual(['wide', 'portrait'])
        expect(wrapper.vm.queue[1].file.name).toBe('portrait.jpg')
    })

    it('selects a resumable upload for files above the configured threshold', () => {
        const wrapper = mount(FormFileUploader, {
            props: {
                uploadRoute: '/attachments',
                chunkUploadRoute: '/chunked',
                chunkThreshold: 10,
            },
            global: { stubs: { 'form-file-upload': { template: '<div />' }, modal: true } },
        })
        const largeFile = new File([new Uint8Array(11)], 'large.bin', {
            type: 'application/octet-stream',
        })
        wrapper.vm.addFile(largeFile)

        expect(wrapper.vm.shouldUseChunks(wrapper.vm.queue[0])).toBe(true)
    })
})

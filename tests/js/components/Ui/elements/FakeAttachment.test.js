import { mount } from '@vue/test-utils'
import FakeAttachment from '@/components/Ui/elements/FakeAttachment.vue'

describe('FakeAttachment', () => {
    it('renders title and extension', () => {
        const w = mount(FakeAttachment, { props: { title: 'Report', extension: 'pdf' } })
        expect(w.text()).toContain('Report')
        expect(w.text()).toContain('.pdf')
    })
    it('shows description when provided', () => {
        const w = mount(FakeAttachment, { props: { title: 'T', extension: 'pdf', description: 'Bericht 2024' } })
        expect(w.text()).toContain('Bericht 2024')
    })
    it('shows size when provided', () => {
        const w = mount(FakeAttachment, { props: { title: 'T', extension: 'pdf', size: '1 MB' } })
        expect(w.text()).toContain('1 MB')
    })
    it('sets window.location.href when href provided and useInertia=false', async () => {
        const orig = window.location
        delete window.location
        window.location = { href: '' }
        const w = mount(FakeAttachment, { props: { title: 'T', extension: 'pdf', href: '/download/1' } })
        await w.trigger('click')
        expect(window.location.href).toBe('/download/1')
        window.location = orig
    })
    it('calls $inertia.get when useInertia=true', async () => {
        const inertiaGet = vi.fn()
        const w = mount(FakeAttachment, {
            props: { title: 'T', extension: 'pdf', href: '/download/1', useInertia: true },
            global: { mocks: { $inertia: { get: inertiaGet } } },
        })
        await w.trigger('click')
        expect(inertiaGet).toHaveBeenCalledWith('/download/1')
    })
    it('emits "download" when no href provided', async () => {
        const w = mount(FakeAttachment, { props: { title: 'T', extension: 'pdf' } })
        await w.trigger('click')
        expect(w.emitted('download')).toHaveLength(1)
    })
})

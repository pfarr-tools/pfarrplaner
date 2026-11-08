import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AttachmentImageEditor from '@/components/Ui/forms/AttachmentImageEditor.vue'

const file = new File(['image'], 'photo.jpg', { type: 'image/jpeg' })
const mountEditor = (props = {}) => mount(AttachmentImageEditor, { props: { file, ...props } })

describe('AttachmentImageEditor', () => {
    it('supports zoom beyond three times', () => {
        const wrapper = mountEditor()
        const zoom = wrapper.find('input[aria-label="Zoom"]')

        expect(zoom.attributes('max')).toBe('8')
        expect(zoom.attributes('min')).toBe('.25')
    })

    it('supports Ctrl plus mouse wheel zooming in both directions', async () => {
        const wrapper = mountEditor()
        wrapper.vm.currentState.zoom = 1

        await wrapper.find('.editor-stage').element.dispatchEvent(new WheelEvent('wheel', {ctrlKey: true, deltaY: 100}))
        expect(wrapper.vm.currentState.zoom).toBeCloseTo(.9)

        await wrapper.find('.editor-stage').element.dispatchEvent(new WheelEvent('wheel', {ctrlKey: true, deltaY: -100}))
        await wrapper.vm.$nextTick()
        expect(wrapper.vm.currentState.zoom).toBeCloseTo(1)
    })

    it('keeps rotation and both mirror axes in one transform', () => {
        const wrapper = mountEditor()

        wrapper.vm.rotate(37)
        wrapper.vm.flip('x')
        wrapper.vm.flip('y')

        expect(wrapper.vm.currentState).toMatchObject({ rotation: 37, flipX: true, flipY: true })
        expect(wrapper.vm.imageStyle.transform).toContain('rotate(37deg)')
        expect(wrapper.vm.imageStyle.transform).toContain('scaleX(-1)')
        expect(wrapper.vm.imageStyle.transform).toContain('scaleY(-1)')
    })

    it('exposes all selected targets for independent variants', () => {
        const presets = [
            { cut: 'wide', title: 'Breit', width: 1200, height: 630 },
            { cut: 'portrait', title: 'Hochformat', width: 1080, height: 1350 },
        ]
        const wrapper = mountEditor({ presets })

        wrapper.vm.selectedTargets = ['wide', 'portrait']
        wrapper.vm.selectTarget('wide')

        expect(wrapper.vm.activeTargets.map(target => target.key)).toEqual(['wide', 'portrait'])
        expect(wrapper.findAll('.target-option')).toHaveLength(3)
    })

    it('renders a local file with the requested name', async () => {
        const wrapper = mountEditor()
        const canvas = { toBlob: callback => callback(new Blob(['cropped'], { type: 'image/jpeg' })) }

        const output = await wrapper.vm.renderFile(canvas, 'wide')

        expect(output).toBeInstanceOf(File)
        expect(output.type).toBe('image/jpeg')
        expect(output.name).toBe('photo-wide.jpg')
    })
})

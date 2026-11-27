import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'
import SignaturePad from '@/Components/Forms/SignaturePad.vue'

describe('SignaturePad', () => {
    const context = {
        beginPath() {},
        moveTo() {},
        lineTo() {},
        stroke() {},
        clearRect() {},
        fillRect() {},
        scale() {},
        setTransform() {},
        closePath() {},
    }

    beforeEach(() => {
        window.HTMLCanvasElement.prototype.getContext = () => context
        window.HTMLCanvasElement.prototype.toDataURL = () => 'data:image/png;base64,signature'
    })

    it('emits a PNG data URL after pointer input', async () => {
        const wrapper = mount(SignaturePad, {
            props: { label: 'Anna Beispiel', modelValue: '' },
        })
        await wrapper.get('canvas').trigger('pointerdown', {
            clientX: 10,
            clientY: 10,
            pointerId: 1,
            isPrimary: true,
            buttons: 1,
        })
        await wrapper.get('canvas').trigger('pointermove', {
            clientX: 20,
            clientY: 20,
            pointerId: 1,
            isPrimary: true,
            buttons: 1,
        })
        await wrapper.get('canvas').trigger('pointerup', { pointerId: 1, isPrimary: true })

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
            'data:image/png;base64,signature',
        ])
        expect(wrapper.get('[data-testid="signature-state"]').text()).toContain('erfasst')
    })

    it('clears the signature and reports an empty required state', async () => {
        const wrapper = mount(SignaturePad, {
            props: {
                label: 'Anna Beispiel',
                modelValue: 'data:image/png;base64,signature',
                required: true,
            },
        })

        await wrapper.get('[data-testid="clear-signature"]').trigger('click')

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([''])
        expect(wrapper.get('[data-testid="signature-state"]').text()).toContain('erforderlich')
    })
})

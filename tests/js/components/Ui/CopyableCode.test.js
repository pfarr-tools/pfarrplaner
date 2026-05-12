import { mount } from '@vue/test-utils'
import CopyableCode from '@/components/Ui/CopyableCode.vue'

describe('CopyableCode', () => {
    let writeText

    beforeEach(() => {
        writeText = vi.fn().mockResolvedValue(undefined)
        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText },
            configurable: true,
            writable: true,
        })
    })

    it('renders content in a <code> element', () => {
        const w = mount(CopyableCode, { props: { content: 'echo hello' } })
        expect(w.find('code').text()).toBe('echo hello')
    })
    it('does not show "copied" message initially', () => {
        const w = mount(CopyableCode, { props: { content: 'echo hello' } })
        expect(w.find('.text-muted').exists()).toBe(false)
    })
    it('calls clipboard.writeText with the content when copy button is clicked', async () => {
        const w = mount(CopyableCode, { props: { content: 'echo hello' } })
        await w.find('a').trigger('click')
        expect(writeText).toHaveBeenCalledWith('echo hello')
    })
    it('shows success message after copy', async () => {
        const w = mount(CopyableCode, { props: { content: 'echo hello' } })
        await w.find('a').trigger('click')
        await new Promise(r => setTimeout(r, 0)) // flush promise
        await w.vm.$nextTick()
        expect(w.find('.text-muted').exists()).toBe(true)
    })
})

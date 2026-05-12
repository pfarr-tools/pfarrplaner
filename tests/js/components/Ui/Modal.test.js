import { mount } from '@vue/test-utils'
import Modal from '@/components/Ui/modals/Modal.vue'

describe('Modal', () => {
    it('renders title in modal-header', () => {
        const w = mount(Modal, { props: { title: 'Mein Dialog' } })
        expect(w.find('.modal-title').text()).toBe('Mein Dialog')
    })
    it('renders slot content in modal-body', () => {
        const w = mount(Modal, {
            props: { title: 'T' },
            slots: { default: '<p>Body text</p>' },
        })
        expect(w.find('.modal-body').text()).toBe('Body text')
    })
    it('shows close button by default (allowClose=true)', () => {
        const w = mount(Modal, { props: { title: 'T' } })
        const closeBtn = w.findAll('.modal-footer button').find(b => b.text().includes('Schließen'))
        expect(closeBtn).toBeDefined()
    })
    it('shows cancel button by default (allowCancel=true)', () => {
        const w = mount(Modal, { props: { title: 'T' } })
        const cancelBtn = w.findAll('.modal-footer button').find(b => b.text().includes('Abbrechen'))
        expect(cancelBtn).toBeDefined()
    })
    it('hides cancel button when allowCancel=false', () => {
        const w = mount(Modal, { props: { title: 'T', allowCancel: false } })
        const cancelBtn = w.findAll('.modal-footer button').find(b => b.text().includes('Abbrechen'))
        expect(cancelBtn).toBeUndefined()
    })
    it('hides close button when allowClose=false', () => {
        const w = mount(Modal, { props: { title: 'T', allowClose: false } })
        const closeBtn = w.findAll('.modal-footer button').find(b => b.text().includes('Schließen'))
        expect(closeBtn).toBeUndefined()
    })
    it('emits "close" when close button is clicked', async () => {
        const w = mount(Modal, { props: { title: 'T' } })
        const closeBtn = w.findAll('.modal-footer button').find(b => b.text().includes('Schließen'))
        await closeBtn.trigger('click')
        expect(w.emitted('close')).toHaveLength(1)
    })
    it('emits "cancel" with "button" when cancel button is clicked', async () => {
        const w = mount(Modal, { props: { title: 'T' } })
        const cancelBtn = w.findAll('.modal-footer button').find(b => b.text().includes('Abbrechen'))
        await cancelBtn.trigger('click')
        expect(w.emitted('cancel')[0]).toEqual(['button'])
    })
    it('uses custom close button label', () => {
        const w = mount(Modal, { props: { title: 'T', closeButtonLabel: 'OK' } })
        const btn = w.findAll('.modal-footer button').find(b => b.text() === 'OK')
        expect(btn).toBeDefined()
    })
    it('adds modal-open class to document.body on mount', () => {
        mount(Modal, { props: { title: 'T' } })
        expect(document.body.classList.contains('modal-open')).toBe(true)
    })
    it('emits "cancel" with "esc" on Esc key when allowCancel=true', async () => {
        // @keyup.esc is on the inner .modal div, not the root <form>
        const w = mount(Modal, { props: { title: 'T', allowCancel: true } })
        await w.find('.modal').trigger('keyup', { key: 'Escape' })
        expect(w.emitted('cancel')?.[0]).toEqual(['esc'])
    })
})

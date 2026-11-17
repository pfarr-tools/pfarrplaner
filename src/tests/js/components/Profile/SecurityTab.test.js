import { flushPromises, mount } from '@vue/test-utils'
import axios from 'axios'
import { afterEach, describe, expect, it, vi } from 'vitest'
import SecurityTab from '@/components/Profile/Tabs/SecurityTab.vue'

vi.mock('qrcode', () => ({
    default: { toDataURL: vi.fn(() => 'data:image/png;base64,local-qr-code') },
}))

afterEach(() => vi.restoreAllMocks())

/** Mount the profile security panel with the required profile props. */
const mountSecurityTab = (props = {}, mountOptions = {}) =>
    mount(SecurityTab, {
        props: {
            user: { id: 1 },
            password: { current: '', new: '', confirm: '' },
            ...props,
        },
        global: {
            stubs: {
                'form-input': {
                    template:
                        '<input v-bind="$attrs" :name="name" :type="type" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                    props: ['name', 'type', 'modelValue'],
                    emits: ['update:modelValue'],
                },
            },
        },
        ...mountOptions,
    })

describe('SecurityTab', () => {
    it('opens the guided TOTP setup popup from the single setup action', async () => {
        vi.spyOn(axios, 'post').mockResolvedValueOnce({
            data: {
                provisioning_uri:
                    'otpauth://totp/Pfarrplaner:test@example.com?secret=JBSWY3DPEHPK3PXP',
                recovery_codes: ['first-code'],
            },
        })
        const wrapper = mountSecurityTab()

        expect(wrapper.text()).toContain('Zwei-Faktor-Authentifizierung einrichten')
        expect(wrapper.find('[data-dusk="two-factor-setup"]').exists()).toBe(false)
        const setupButton = wrapper.find('[data-testid="open-two-factor-setup"]')
        expect(setupButton.exists()).toBe(true)
        await setupButton.trigger('click')

        expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
        expect(wrapper.text()).toContain('Authenticator-App')
        expect(wrapper.text()).toContain('Microsoft Authenticator')
        expect(wrapper.text()).toContain('2FAS Auth')
        expect(wrapper.find('[name="two_factor_current_password"]').exists()).toBe(false)
    })

    it('keeps keyboard focus in the setup dialog and restores it when closed', async () => {
        const wrapper = mountSecurityTab({}, { attachTo: document.body })
        const setupButton = wrapper.find('[data-testid="open-two-factor-setup"]')
        await setupButton.trigger('click')
        const dialog = wrapper.find('[role="dialog"]')
        const firstAction = wrapper.find('[data-testid="two-factor-intro-next"]')

        await dialog.trigger('keydown', { key: 'Tab' })
        expect(document.activeElement).toBe(firstAction.element)

        await dialog.trigger('keydown', { key: 'Escape' })
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
        expect(document.activeElement).toBe(setupButton.element)
    })

    it('shows the disable action for an enabled account', () => {
        const wrapper = mountSecurityTab({ twoFactorEnabled: true })

        expect(wrapper.text()).toContain('Zwei-Faktor-Authentifizierung deaktivieren')
        expect(wrapper.text()).not.toContain('Zwei-Faktor-Authentifizierung einrichten')
    })

    it('reauthenticates with the current password when disabling TOTP', async () => {
        const post = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { enabled: false } })
        const wrapper = mountSecurityTab({ twoFactorEnabled: true })
        await wrapper.find('[name="two_factor_current_password"]').setValue('Current1!')
        await wrapper.find('button.btn-outline-danger').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenCalledWith('/user.two-factor.disable', {
            current_password: 'Current1!',
        })
    })

    it('shows TOTP setup failures inside the active dialog', async () => {
        vi.spyOn(axios, 'post').mockRejectedValueOnce({ response: { status: 422 } })
        const wrapper = mountSecurityTab()
        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        await wrapper.find('[name="two_factor_current_password"]').setValue('Wrong1!')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.find('[role="dialog"] [role="alert"]').exists()).toBe(true)
    })

    it('renders a local QR code and recovery codes during guided setup', async () => {
        const wrapper = mountSecurityTab()
        wrapper.vm.setup = {
            provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=JBSWY3DPEHPK3PXP',
            recovery_codes: ['first-code', 'second-code'],
        }
        wrapper.vm.qrCode = 'data:image/png;base64,local-qr-code'
        wrapper.vm.setupStep = 3
        wrapper.vm.setupModalOpen = true
        await wrapper.vm.$nextTick()

        expect(wrapper.find('[data-dusk="two-factor-setup"]').exists()).toBe(true)
        const qrCode = wrapper.find('[data-testid="two-factor-qr"]')
        expect(qrCode.exists()).toBe(true)
        expect(qrCode.attributes('src')).toMatch(/^data:image\//)
        expect(wrapper.text()).toContain('first-code')
        expect(wrapper.text()).toContain('second-code')
        expect(wrapper.text()).toContain('JBSWY3DPEHPK3PXP')
    })

    it('starts TOTP setup and confirms a six-digit code through the wizard', async () => {
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce({
            data: {
                provisioning_uri:
                    'otpauth://totp/Pfarrplaner:test@example.com?secret=JBSWY3DPEHPK3PXP',
                recovery_codes: ['one-time-code'],
            },
        })
        post.mockResolvedValueOnce({ data: { enabled: true } })
        const wrapper = mountSecurityTab()

        const openButton = wrapper.find('[data-testid="open-two-factor-setup"]')
        expect(openButton.exists()).toBe(true)
        await openButton.trigger('click')
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        const passwordInput = wrapper.find('[name="two_factor_current_password"]')
        await passwordInput.setValue('Current1!')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(1, '/user.two-factor.start', {
            current_password: 'Current1!',
        })
        expect(wrapper.text()).toContain('one-time-code')
        expect(wrapper.find('[data-testid="two-factor-qr"]').exists()).toBe(true)

        await wrapper.find('[data-testid="recovery-codes-saved"]').setValue(true)
        await wrapper.find('[data-testid="two-factor-setup-next"]').trigger('click')
        await wrapper.find('[name="two_factor_current_password"]').setValue('Current1!')
        const firstCodeInput = wrapper.find('[data-testid="totp-digit-0"]')
        await firstCodeInput.setValue('123456')
        expect(
            wrapper.findAll('[data-testid^="totp-digit-"]').map((input) => input.element.value),
        ).toEqual(['1', '2', '3', '4', '5', '6'])

        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(2, '/user.two-factor.confirm', {
            current_password: 'Current1!',
            code: '123456',
        })
        expect(wrapper.emitted('two-factor-enabled')).toHaveLength(1)
        expect(wrapper.text()).toContain('Die Zwei-Faktor-Authentifizierung ist aktiviert')
        expect(wrapper.find('[data-dusk="two-factor-setup"]').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('one-time-code')
        expect(wrapper.text()).not.toContain('JBSWY3DPEHPK3PXP')
        expect(wrapper.vm.twoFactor.currentPassword).toBe('')
        post.mockRestore()
    })
})

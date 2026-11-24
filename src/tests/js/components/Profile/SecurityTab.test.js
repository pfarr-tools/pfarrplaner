import { flushPromises, mount } from '@vue/test-utils'
import axios from 'axios'
import { afterEach, describe, expect, it, vi } from 'vitest'
import SecurityTab from '@/components/Profile/Tabs/SecurityTab.vue'
import { PasskeyRegistration } from '@/lib/passkeyRegistration'

vi.mock('qrcode', () => ({
    default: { toDataURL: vi.fn(() => 'data:image/png;base64,local-qr-code') },
}))

const originalClipboardDescriptor = Object.getOwnPropertyDescriptor(window.navigator, 'clipboard')

afterEach(() => {
    vi.restoreAllMocks()
    if (originalClipboardDescriptor) {
        Object.defineProperty(window.navigator, 'clipboard', originalClipboardDescriptor)
    } else {
        delete window.navigator.clipboard
    }
})

/** Mount the profile security panel. */
const mountSecurityTab = (props = {}, mountOptions = {}) =>
    mount(SecurityTab, {
        props: { user: { id: 1, email: 'test@example.com' }, ...props },
        global: mountOptions.global,
        attachTo: mountOptions.attachTo,
    })

describe('SecurityTab', () => {
    it('contains no password controls, requirements, or copy', () => {
        const wrapper = mountSecurityTab({ twoFactorEnabled: true })
        const content = wrapper.text().toLocaleLowerCase('de')

        expect(wrapper.findAll('input[type="password"]')).toHaveLength(0)
        expect(content).not.toContain('passwort')
        expect(content).not.toContain('mindestens 8 zeichen')
    })

    it('starts TOTP setup without sending account-password data', async () => {
        /** Build a TOTP setup response fixture.
         *
         * @param {string} secret Provisioning secret.
         * @param {string} code Recovery code.
         * @returns {{ data: { provisioning_uri: string, recovery_codes: string[] } }}
         */
        const setupResponse = (secret, code) => ({
            data: {
                provisioning_uri: `otpauth://totp/Pfarrplaner:test@example.com?secret=${secret}`,
                recovery_codes: [code],
            },
        })
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce(setupResponse('FIRSTSECRET', 'first-code'))
        post.mockResolvedValueOnce(setupResponse('SECONDSECRET', 'second-code'))
        const wrapper = mountSecurityTab()

        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        expect(wrapper.find('[data-testid="two-factor-intro-next"]').exists()).toBe(true)
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenCalledWith('/user.two-factor.start', {})
        expect(wrapper.vm.factorPending).toBe(true)
        expect(wrapper.find('[data-testid="recovery-codes"]').element.value).toBe('first-code')

        await wrapper.find('[data-testid="two-factor-setup-cancel"]').trigger('click')
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="open-two-factor-setup"]').text()).toContain(
            'Einrichtung neu starten',
        )
        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenCalledTimes(2)
        expect(wrapper.find('[data-testid="recovery-codes"]').element.value).toBe('second-code')
    })

    it('sends only one setup request when the start button is clicked twice quickly', async () => {
        let resolveRequest
        const post = vi.spyOn(axios, 'post').mockReturnValueOnce(
            new Promise((resolve) => {
                resolveRequest = resolve
            }),
        )
        const wrapper = mountSecurityTab()
        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')

        wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        expect(post).toHaveBeenCalledOnce()

        resolveRequest({
            data: {
                provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=SECRET',
                recovery_codes: ['one-time-code'],
            },
        })
        await flushPromises()
        expect(wrapper.find('[data-testid="recovery-codes"]').exists()).toBe(true)
    })

    it('does not dismiss setup while the start request is pending', async () => {
        let resolveRequest
        vi.spyOn(axios, 'post').mockReturnValueOnce(
            new Promise((resolve) => {
                resolveRequest = resolve
            }),
        )
        const wrapper = mountSecurityTab()
        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')

        await wrapper.find('[role="dialog"]').trigger('keydown.esc')
        await wrapper.find('.setup-backdrop').trigger('click')
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true)

        resolveRequest({
            data: {
                provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=SECRET',
                recovery_codes: ['one-time-code'],
            },
        })
        await flushPromises()
        expect(wrapper.find('[data-testid="recovery-codes"]').exists()).toBe(true)
        await wrapper.find('[data-testid="two-factor-setup-cancel"]').trigger('click')
        expect(wrapper.vm.setup).toBeNull()
    })

    it('keeps dialog focus contained and clears setup secrets when closed', async () => {
        const wrapper = mountSecurityTab({}, { attachTo: document.body })
        const trigger = wrapper.find('[data-testid="open-two-factor-setup"]')
        await trigger.trigger('click')
        const dialog = wrapper.find('[role="dialog"]')
        await dialog.trigger('keydown', { key: 'Tab' })
        expect(document.activeElement).toBe(
            wrapper.find('[data-testid="two-factor-setup-close"]').element,
        )

        wrapper.vm.setup = {
            provisioning_uri: 'otpauth://totp/a?secret=SECRET',
            recovery_codes: ['CODE'],
        }
        wrapper.vm.qrCode = 'data:image/png;base64,secret'
        wrapper.vm.twoFactor.code = '123456'
        await wrapper.find('[data-testid="two-factor-setup-cancel"]').trigger('click')

        expect(wrapper.vm.setup).toBeNull()
        expect(wrapper.vm.qrCode).toBe('')
        expect(wrapper.vm.twoFactor.code).toBe('')
        expect(document.activeElement).toBe(trigger.element)
        wrapper.unmount()
    })

    it('copies setup keys and recovery codes and reports clipboard failures', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined)
        Object.defineProperty(window.navigator, 'clipboard', {
            configurable: true,
            value: { writeText },
        })
        const wrapper = mountSecurityTab()
        wrapper.vm.setup = {
            provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=SECRETKEY',
            recovery_codes: ['first-code', 'second-code'],
        }
        wrapper.vm.setupStep = 2
        wrapper.vm.setupModalOpen = true
        await wrapper.vm.$nextTick()

        await wrapper.find('[data-testid="copy-totp-manual-key"]').trigger('click')
        expect(writeText).toHaveBeenCalledWith('SECRETKEY')
        await wrapper.find('[data-testid="copy-recovery-codes"]').trigger('click')
        expect(writeText).toHaveBeenCalledWith('first-code\nsecond-code')
        expect(wrapper.find('[role="status"]').text()).toContain('Alle Recovery-Codes kopiert')

        writeText.mockRejectedValueOnce(new Error('denied'))
        await wrapper.find('[data-testid="copy-recovery-codes"]').trigger('click')
        await flushPromises()
        expect(wrapper.find('[role="alert"]').text()).toContain('Bitte markiere ihn')
        expect(wrapper.find('[role="status"]').text()).toBe('')
    })

    it('confirms TOTP with its code only and removes it after a simple confirmation', async () => {
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce({
            data: {
                provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=SECRET',
                recovery_codes: ['one-time-code'],
            },
        })
        post.mockResolvedValueOnce({ data: { enabled: true } })
        post.mockResolvedValueOnce({ data: { enabled: false } })
        const wrapper = mountSecurityTab()
        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        await flushPromises()
        await wrapper.find('[data-testid="recovery-codes-saved"]').setValue(true)
        await wrapper.find('[data-testid="two-factor-setup-next"]').trigger('click')
        for (const [index, digit] of [...'123456'].entries()) {
            await wrapper.find(`[data-testid="totp-digit-${index}"]`).setValue(digit)
        }
        expect(wrapper.vm.twoFactor.code).toBe('123456')
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(1, '/user.two-factor.start', {})
        expect(post).toHaveBeenNthCalledWith(2, '/user.two-factor.confirm', { code: '123456' })
        expect(wrapper.emitted('two-factor-enabled')).toHaveLength(1)

        vi.spyOn(window, 'confirm').mockReturnValueOnce(true)
        await wrapper.find('[data-testid="disable-two-factor"]').trigger('click')
        await flushPromises()
        expect(post).toHaveBeenNthCalledWith(3, '/user.two-factor.disable', {})
        expect(wrapper.vm.factorEnabled).toBe(false)
    })

    it('leaves TOTP enabled when the user cancels its removal', async () => {
        vi.spyOn(window, 'confirm').mockReturnValueOnce(false)
        const post = vi.spyOn(axios, 'post')
        const wrapper = mountSecurityTab({ twoFactorEnabled: true })

        await wrapper.find('[data-testid="disable-two-factor"]').trigger('click')
        expect(post).not.toHaveBeenCalled()
        expect(wrapper.vm.factorEnabled).toBe(true)
    })

    it('shows the TOTP code validation message so users can correct their input', async () => {
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce({
            data: {
                provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=SECRET',
                recovery_codes: ['one-time-code'],
            },
        })
        post.mockRejectedValueOnce({
            response: {
                status: 422,
                data: {
                    errors: {
                        code: ['Der Sicherheitscode ist ungültig oder nicht mehr verfügbar.'],
                    },
                },
            },
        })
        const wrapper = mountSecurityTab()
        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        await flushPromises()
        await wrapper.find('[data-testid="recovery-codes-saved"]').setValue(true)
        await wrapper.find('[data-testid="two-factor-setup-next"]').trigger('click')
        for (const [index, digit] of [...'123456'].entries()) {
            await wrapper.find(`[data-testid="totp-digit-${index}"]`).setValue(digit)
        }
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toContain(
            'Der Sicherheitscode ist ungültig',
        )
        expect(wrapper.find('[data-testid="totp-digit-0"]').exists()).toBe(true)
    })

    it('registers a discoverable passkey without an account-password dialog', async () => {
        const register = vi.spyOn(PasskeyRegistration, 'register').mockResolvedValue({
            id: 7,
            name: 'Arbeitslaptop',
        })
        const wrapper = mountSecurityTab()

        await wrapper.find('[data-testid="register-passkey"]').trigger('click')
        await wrapper.find('[data-testid="passkey-name"]').setValue('Arbeitslaptop')
        await wrapper.find('[data-testid="confirm-passkey-setup"]').trigger('click')
        await flushPromises()

        expect(register).toHaveBeenCalledWith({
            name: 'Arbeitslaptop',
            routes: {
                options: '/user.passkeys.options',
                submit: '/user.passkeys.store',
                diagnostics: '/user.passkeys.diagnostics',
            },
        })
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
        expect(wrapper.find('#passkey-label-7').element.value).toBe('Arbeitslaptop')
    })

    it('shows the phase-specific registration error and keeps the email fallback visible', async () => {
        vi.spyOn(PasskeyRegistration, 'register').mockRejectedValue(
            new Error(
                'Gerät/Browser: Der Browser oder Passkey-Anbieter konnte den Passkey nicht erstellen. Du kannst dich weiterhin per E-Mail-Link anmelden. Diagnose-ID: 1234',
            ),
        )
        const wrapper = mountSecurityTab()

        await wrapper.find('[data-testid="register-passkey"]').trigger('click')
        await wrapper.find('[data-testid="passkey-name"]').setValue('Arbeitslaptop')
        await wrapper.find('[data-testid="confirm-passkey-setup"]').trigger('click')
        await flushPromises()

        const alert = wrapper.find('[role="dialog"] [role="alert"]')
        expect(alert.text()).toContain('Gerät/Browser')
        expect(alert.text()).toContain('weiterhin per E-Mail-Link anmelden')
        expect(alert.text()).toContain('Diagnose-ID: 1234')
        expect(wrapper.vm.passkeyBusy).toBe(false)
    })

    it('manages passkeys through the authenticated account routes without password dialogs', async () => {
        const patch = vi.spyOn(axios, 'patch').mockResolvedValue({ data: { name: 'Office key' } })
        const remove = vi.spyOn(axios, 'delete').mockResolvedValue({})
        vi.spyOn(window, 'confirm').mockReturnValue(true)
        const wrapper = mountSecurityTab({
            passkeys: [{ id: 7, name: 'Office key', authenticator: 'Passkey' }],
        })

        await wrapper.find('[data-testid="rename-passkey"]').trigger('click')
        await flushPromises()
        await wrapper.find('[data-testid="revoke-passkey"]').trigger('click')
        await flushPromises()

        expect(patch).toHaveBeenCalledWith('/user.passkeys.rename', { name: 'Office key' })
        expect(remove).toHaveBeenCalledOnce()
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    })
})

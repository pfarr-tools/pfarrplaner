import { flushPromises, mount } from '@vue/test-utils'
import axios from 'axios'
import { Passkeys } from '@laravel/passkeys'
import { afterEach, vi } from 'vitest'
import { describe, expect, it } from 'vitest'
import SecurityOnboarding from '@/Pages/Auth/SecurityOnboarding.vue'

vi.mock('qrcode', () => ({
    default: { toDataURL: vi.fn(() => 'data:image/png;base64,local-qr-code') },
}))

afterEach(() => {
    vi.restoreAllMocks()
})

/** Mount the account security onboarding page with the shared factor setup UI stubbed. */
const mountOnboarding = () =>
    mount(SecurityOnboarding, {
        props: {
            user: { firstName: 'Ada', email: 'ada@example.org' },
            twoFactorEnabled: false,
            twoFactorPending: false,
            passkeys: [],
        },
        global: {
            stubs: {
                SecurityTab: {
                    props: ['user', 'twoFactorEnabled', 'twoFactorPending', 'passkeys'],
                    template: '<div data-testid="factor-setup" />',
                },
            },
        },
    })

describe('SecurityOnboarding', () => {
    it('explains both options and embeds the direct setup controls', () => {
        const wrapper = mountOnboarding()

        expect(wrapper.text()).toContain('Passkey: die einfachste Anmeldung')
        expect(wrapper.text()).toContain('Authenticator-App: Einmalcodes als zusätzlicher Schutz')
        expect(wrapper.text()).toContain('Microsoft Authenticator')
        expect(wrapper.text()).toContain('2FAS Auth')
        expect(wrapper.find('[data-testid="factor-setup"]').exists()).toBe(true)
    })

    it('starts and confirms TOTP from the onboarding page itself', async () => {
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce({
            data: {
                provisioning_uri: 'otpauth://totp/Pfarrplaner:ada@example.org?secret=SECRET',
                recovery_codes: ['recovery-code'],
            },
        })
        post.mockResolvedValueOnce({ data: { enabled: true } })
        const wrapper = mount(SecurityOnboarding, {
            props: {
                user: { firstName: 'Ada', email: 'ada@example.org' },
                twoFactorEnabled: false,
                twoFactorPending: false,
                passkeys: [],
            },
            global: { stubs: { FormCsrfToken: true } },
        })

        await wrapper.find('[data-testid="open-two-factor-setup"]').trigger('click')
        await wrapper.find('[data-testid="two-factor-intro-next"]').trigger('click')
        await flushPromises()
        await wrapper.find('[data-testid="recovery-codes-saved"]').setValue(true)
        await wrapper.find('[data-testid="two-factor-setup-next"]').trigger('click')
        for (const [index, digit] of [...'123456'].entries()) {
            await wrapper.find(`[data-testid="totp-digit-${index}"]`).setValue(digit)
        }
        await wrapper.find('#two-factor-confirm-form').trigger('submit')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(1, '/user.two-factor.start', {})
        expect(post).toHaveBeenNthCalledWith(2, '/user.two-factor.confirm', { code: '123456' })
        expect(wrapper.find('[data-testid="continue-after-setup"]').exists()).toBe(true)
    })

    it('starts passkey registration from the onboarding page itself', async () => {
        const register = vi.spyOn(Passkeys, 'register').mockResolvedValue({
            id: 7,
            name: 'Arbeitslaptop',
        })
        const wrapper = mount(SecurityOnboarding, {
            props: {
                user: { firstName: 'Ada', email: 'ada@example.org' },
                twoFactorEnabled: false,
                twoFactorPending: false,
                passkeys: [],
            },
            global: { stubs: { FormCsrfToken: true } },
        })

        await wrapper.find('[data-testid="passkey-name"]').setValue('Arbeitslaptop')
        await wrapper.find('[data-testid="register-passkey"]').trigger('click')
        await flushPromises()

        expect(register).toHaveBeenCalledWith({
            name: 'Arbeitslaptop',
            routes: {
                options: '/user.passkeys.options',
                submit: '/user.passkeys.store',
            },
        })
    })

    it('offers application access after setup and a session-only deferral', () => {
        const wrapper = mountOnboarding()

        expect(wrapper.find('[data-testid="continue-after-setup"]').attributes('href')).toBe(
            '/home',
        )
        expect(wrapper.find('[data-testid="defer-onboarding"]').text()).toBe('Später erinnern')
        expect(wrapper.find('form').attributes('action')).toBe('/security.onboarding.later')
    })
})

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import SecurityOnboarding from '@/Pages/Auth/SecurityOnboarding.vue'

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

    it('offers application access after setup and a session-only deferral', () => {
        const wrapper = mountOnboarding()

        expect(wrapper.find('[data-testid="continue-after-setup"]').attributes('href')).toBe(
            '/home',
        )
        expect(wrapper.find('[data-testid="defer-onboarding"]').text()).toBe('Später erinnern')
        expect(wrapper.find('form').attributes('action')).toBe('/security.onboarding.later')
    })
})

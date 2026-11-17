import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import InterstitialOverlay from '@/components/Ui/Interstitials/InterstitialOverlay.vue'

afterEach(() => vi.restoreAllMocks())

/** Mount the interstitial with the client services it uses. */
const mountOverlay = (interstitial, post = vi.fn(), inertiaGet = vi.fn()) => {
    const api = vi.fn().mockReturnValue({ post })

    return mount(InterstitialOverlay, {
        global: {
            mocks: {
                $page: { props: { interstitials: [interstitial], sessionId: 'session-1' } },
                $api: api,
                $inertia: { get: inertiaGet },
                $settings: { interstitials: {} },
            },
        },
    })
}

describe('InterstitialOverlay', () => {
    it('shows the recommended action and omits permanent dismissal when required', () => {
        const wrapper = mountOverlay({
            key: 'security-beyond-password',
            title: 'Schützen Sie Ihr Konto',
            text: 'Passkey zuerst, TOTP als Alternative.',
            details: ['Ein starkes Passwort bleibt der Rückfallweg.'],
            repeatable: true,
            dismissible: false,
            action_label: 'Sicherheitseinstellungen öffnen',
            action_url: '/profil?tab=security',
        })

        expect(wrapper.find('a[href="/profil?tab=security"]').text()).toBe(
            'Sicherheitseinstellungen öffnen',
        )
        expect(wrapper.text()).toContain('Passkey zuerst, TOTP als Alternative.')
        expect(wrapper.text()).toContain('Ein starkes Passwort bleibt der Rückfallweg.')
        expect(wrapper.text()).toContain('Später')
        expect(wrapper.text()).not.toContain('Nicht wieder anzeigen')
    })

    it('keeps a repeatable interstitial hidden until its server-provided snooze expires', async () => {
        const snoozedUntil = new Date(Date.now() + 21 * 24 * 60 * 60 * 1000).toISOString()
        const post = vi.fn().mockResolvedValue({
            data: { status: 'later', snoozed_until: snoozedUntil },
        })
        const wrapper = mountOverlay(
            {
                key: 'security-beyond-password',
                title: 'Schützen Sie Ihr Konto',
                text: 'Passkey zuerst.',
                details: [],
                repeatable: true,
                dismissible: false,
            },
            post,
        )

        await wrapper.find('button').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenCalledWith('/interstitial.update', { action: 'later' })
        expect(wrapper.vm.currentInterstitial).toBeNull()

        wrapper.vm.currentTime = Date.parse(snoozedUntil) + 1
        expect(wrapper.vm.currentInterstitial.key).toBe('security-beyond-password')
    })

    it('records the snooze before navigating to the recommended setup page', async () => {
        const post = vi.fn().mockResolvedValue({
            data: {
                status: 'later',
                snoozed_until: new Date(Date.now() + 21 * 86400000).toISOString(),
            },
        })
        const inertiaGet = vi.fn()
        const wrapper = mountOverlay(
            {
                key: 'security-beyond-password',
                title: 'Schützen Sie Ihr Konto',
                details: [],
                repeatable: true,
                dismissible: false,
                action_label: 'Passkey einrichten',
                action_url: '/profil?tab=security',
            },
            post,
            inertiaGet,
        )

        await wrapper.find('[data-testid="interstitial-primary-action"]').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenCalledOnce()
        expect(inertiaGet).toHaveBeenCalledWith('/profil?tab=security')
    })
})

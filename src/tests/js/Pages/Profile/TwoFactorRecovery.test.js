import { flushPromises, mount } from '@vue/test-utils'
import axios from 'axios'
import { afterEach, describe, expect, it, vi } from 'vitest'
import TwoFactorRecovery from '@/Pages/Profile/TwoFactorRecovery.vue'

/** Mount the recovery-only TOTP reenrolment page. */
const mountRecoveryPage = () =>
    mount(TwoFactorRecovery, {
        global: {
            stubs: {
                'form-input': {
                    template:
                        '<label>{{ label }}<input :name="name" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></label>',
                    props: ['label', 'name', 'modelValue'],
                    emits: ['update:modelValue'],
                },
            },
        },
    })

afterEach(() => {
    vi.restoreAllMocks()
})

describe('TwoFactorRecovery', () => {
    it('explains the required setup without unrelated profile data', () => {
        const wrapper = mountRecoveryPage()

        expect(wrapper.text()).toContain('Zwei-Faktor-Authentifizierung erneut einrichten')
        expect(wrapper.text().toLowerCase()).not.toContain('passwort')
        expect(wrapper.find('input[type="password"]').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('E-Mail-Adresse ändern')
        expect(wrapper.find('[data-dusk="two-factor-recovery-setup"]').exists()).toBe(false)
    })

    it('shows setup data once and clears TOTP material after confirmation', async () => {
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce({
            data: {
                provisioning_uri:
                    'otpauth://totp/Pfarrplaner:user@example.test?secret=JBSWY3DPEHPK3PXP',
                recovery_codes: ['one-time-recovery-code'],
            },
        })
        post.mockResolvedValueOnce({ data: { enabled: true } })
        const wrapper = mountRecoveryPage()
        await wrapper.find('[data-testid="start-enrolment"]').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(1, '/user.two-factor.start', {})
        expect(wrapper.text()).toContain('JBSWY3DPEHPK3PXP')
        expect(wrapper.text()).toContain('one-time-recovery-code')
        wrapper.vm.code = '123 456'
        await wrapper.find('[data-testid="confirm-enrolment"]').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(2, '/user.two-factor.confirm', {
            code: '123 456',
        })
        expect(wrapper.emitted('enrolment-complete')).toHaveLength(1)
        expect(wrapper.vm.setup).toBeNull()
        expect(wrapper.vm.code).toBe('')
        expect(wrapper.text()).not.toContain('one-time-recovery-code')
        expect(wrapper.text()).not.toContain('JBSWY3DPEHPK3PXP')
    })

    it('shows a generic accessible error when enrolment fails', async () => {
        vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('private detail'))
        const wrapper = mountRecoveryPage()
        await wrapper.find('[data-testid="start-enrolment"]').trigger('click')
        await flushPromises()

        expect(wrapper.find('[role="alert"]').text()).toContain(
            'Die Zwei-Faktor-Einrichtung konnte nicht ausgeführt werden.',
        )
        expect(wrapper.text()).not.toContain('private detail')
    })
})

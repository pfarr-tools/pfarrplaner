import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import TwoFactorRecoveryPanel from '@/Pages/Admin/User/TwoFactorRecoveryPanel.vue'

/** Mount the Super-Admin recovery panel with inert route and Inertia helpers. */
const mountPanel = () => {
    const post = vi.fn((_url, _payload, options) => {
        options?.onSuccess?.()
        options?.onFinish?.()
    })
    const wrapper = mount(TwoFactorRecoveryPanel, {
        props: {
            canRecover: true,
            recoveryRoute: '/user.two-factor.recover/42',
            history: [
                {
                    actor: { id: 7, name: 'Super Admin' },
                    createdAt: '2026-11-16T10:00:00Z',
                    supportReference: 'SUP-80-2026',
                },
            ],
        },
        global: {
            mocks: {
                $inertia: { post },
            },
        },
    })

    return { post, wrapper }
}

afterEach(() => vi.restoreAllMocks())

describe('TwoFactorRecoveryPanel', () => {
    it('does not render recovery controls without explicit authorization', () => {
        const wrapper = mount(TwoFactorRecoveryPanel, {
            props: { recoveryRoute: '/user.two-factor.recover/42', canRecover: false },
        })

        expect(wrapper.find('[data-testid="open-two-factor-recovery"]').exists()).toBe(false)
    })

    it('explains when recovery is unavailable but keeps the history visible', () => {
        const wrapper = mount(TwoFactorRecoveryPanel, {
            props: {
                recoveryRoute: '/user.two-factor.recover/42',
                canRecover: false,
                unavailableReason: 'Datenbank-Sitzungen sind erforderlich.',
                history: [
                    {
                        actor: { id: 7, name: 'Super Admin' },
                        createdAt: '2026-11-16T10:00:00Z',
                        supportReference: 'SUP-80-2026',
                    },
                ],
            },
        })

        expect(wrapper.text()).toContain('Datenbank-Sitzungen sind erforderlich.')
        expect(wrapper.text()).toContain('SUP-80-2026')
        expect(wrapper.find('[data-testid="open-two-factor-recovery"]').exists()).toBe(false)
    })

    it('closes an open form and blocks submission if recovery becomes unavailable', async () => {
        const { post, wrapper } = mountPanel()
        await wrapper.find('[data-testid="open-two-factor-recovery"]').trigger('click')
        await wrapper.find('input[name="current_password"]').setValue('Current1!')
        await wrapper.find('input[name="support_reference"]').setValue('SUP-80-2026')

        await wrapper.setProps({
            canRecover: false,
            unavailableReason: 'Datenbank-Sitzungen sind erforderlich.',
        })
        await wrapper.vm.recover()

        expect(wrapper.find('form').exists()).toBe(false)
        expect(post).not.toHaveBeenCalled()
    })

    it('requires current password and support reference and explains identity verification', async () => {
        const { wrapper } = mountPanel()

        expect(wrapper.text()).toContain('Vor dem Widerruf muss die Identität')
        expect(wrapper.text()).toContain('SUP-80-2026')
        expect(wrapper.text()).toContain('Super Admin')
        expect(wrapper.text()).not.toContain('192.0.2.12')

        await wrapper.find('[data-testid="open-two-factor-recovery"]').trigger('click')
        expect(wrapper.find('input[name="current_password"]').exists()).toBe(true)
        expect(wrapper.find('input[name="support_reference"]').exists()).toBe(true)
    })

    it('cancels without submitting and posts only the reauthentication inputs', async () => {
        const { post, wrapper } = mountPanel()
        await wrapper.find('[data-testid="open-two-factor-recovery"]').trigger('click')
        await wrapper.find('input[name="current_password"]').setValue('Current1!')
        await wrapper.find('input[name="support_reference"]').setValue('SUP-80-2026')
        await wrapper.find('[data-testid="cancel-two-factor-recovery"]').trigger('click')
        expect(post).not.toHaveBeenCalled()

        await wrapper.find('[data-testid="open-two-factor-recovery"]').trigger('click')
        await wrapper.find('input[name="current_password"]').setValue('Current1!')
        await wrapper.find('input[name="support_reference"]').setValue('SUP-80-2026')
        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(post).toHaveBeenCalledWith(
            '/user.two-factor.recover/42',
            {
                current_password: 'Current1!',
                support_reference: 'SUP-80-2026',
            },
            expect.objectContaining({
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
                onFinish: expect.any(Function),
            }),
        )
        expect(wrapper.text()).not.toContain('Current1!')
        expect(wrapper.find('[data-testid="open-two-factor-recovery"]').exists()).toBe(true)
    })
})

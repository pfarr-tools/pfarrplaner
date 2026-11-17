import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import LoginChallenge from '@/Pages/Auth/LoginChallenge.vue'

describe('LoginChallenge', () => {
    it('focuses the active challenge field and follows method switches', async () => {
        const wrapper = mount(LoginChallenge, { attachTo: document.body })
        await nextTick()

        expect(document.activeElement).toBe(
            wrapper.find('[data-testid="login-totp-digit-0"]').element,
        )

        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Recovery-Code'))
            .trigger('click')
        await nextTick()
        expect(document.activeElement).toBe(wrapper.find('#recovery-code').element)

        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Authenticator-App'))
            .trigger('click')
        await nextTick()
        expect(document.activeElement).toBe(
            wrapper.find('[data-testid="login-totp-digit-0"]').element,
        )
        wrapper.unmount()
    })

    it('submits a complete TOTP as soon as the sixth digit is entered', async () => {
        const wrapper = mount(LoginChallenge)
        await wrapper.find('[data-testid="login-totp-digit-0"]').setValue('123456')

        expect(wrapper.vm.$inertia.post).toHaveBeenCalledOnce()
        expect(wrapper.vm.$inertia.post).toHaveBeenCalledWith(
            '/login.challenge.verify',
            { code: '123456' },
            expect.objectContaining({ preserveState: true }),
        )
    })

    it('keeps recovery-code submission manual', async () => {
        const wrapper = mount(LoginChallenge)
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Recovery-Code'))
            .trigger('click')
        await wrapper.find('#recovery-code').setValue('recovery-code')

        expect(wrapper.vm.$inertia.post).not.toHaveBeenCalled()

        await wrapper.find('form').trigger('submit')
        expect(wrapper.vm.$inertia.post).toHaveBeenCalledOnce()
    })

    it('focuses the first email-code field when switching to email verification', async () => {
        const wrapper = mount(LoginChallenge, { attachTo: document.body })
        await wrapper.findAll('form')[1].trigger('submit')
        await nextTick()

        expect(document.activeElement).toBe(
            wrapper.find('[data-testid="email-code-digit-0"]').element,
        )
        wrapper.unmount()
    })

    it('shows a confirmation after requesting an email code', async () => {
        const wrapper = mount(LoginChallenge, {
            global: {
                mocks: {
                    $page: {
                        props: {
                            errors: {},
                            flash: { success: 'Ein Anmeldecode wurde per E-Mail versendet.' },
                        },
                    },
                },
            },
        })
        await nextTick()

        expect(wrapper.text()).toContain('Ein Anmeldecode wurde per E-Mail versendet.')
    })

    it('submits the entered one-time code to the verification route', async () => {
        const wrapper = mount(LoginChallenge)
        await wrapper.find('[data-testid="login-totp-digit-0"]').setValue('123456')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.vm.$inertia.post).toHaveBeenCalledWith(
            '/login.challenge.verify',
            { code: '123456' },
            expect.objectContaining({ preserveState: true }),
        )
    })

    it('offers a recovery code text field as an alternative', async () => {
        const wrapper = mount(LoginChallenge)
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Recovery-Code'))
            .trigger('click')

        expect(wrapper.find('#recovery-code').exists()).toBe(true)
        expect(wrapper.find('[data-testid="login-totp-digit-0"]').exists()).toBe(false)
    })

    it('distributes a pasted six-digit code across the TOTP fields', async () => {
        const wrapper = mount(LoginChallenge)
        await wrapper.find('[data-testid="login-totp-digit-0"]').trigger('paste', {
            clipboardData: { getData: () => '12-34 56' },
        })

        expect(wrapper.vm.code).toBe('123456')
        expect(
            wrapper
                .findAll('[data-testid^="login-totp-digit-"]')
                .map((input) => input.element.value),
        ).toEqual(['1', '2', '3', '4', '5', '6'])
    })

    it('requests email codes using eight numeric fields', async () => {
        const wrapper = mount(LoginChallenge)
        await wrapper.findAll('form')[1].trigger('submit')

        expect(wrapper.vm.codeMode).toBe('email')
        expect(wrapper.findAll('[data-testid^="email-code-digit-"]')).toHaveLength(8)
    })

    it('can request an email code and cancel the pending login', async () => {
        const wrapper = mount(LoginChallenge)
        const forms = wrapper.findAll('form')

        await forms[1].trigger('submit')
        await forms[2].trigger('submit')

        expect(wrapper.vm.$inertia.post).toHaveBeenNthCalledWith(
            1,
            '/login.challenge.email',
            {},
            expect.any(Object),
        )
        expect(wrapper.vm.$inertia.post).toHaveBeenNthCalledWith(2, '/login.challenge.cancel')
        expect(vi.mocked(window.route)).toHaveBeenCalled()
    })
})

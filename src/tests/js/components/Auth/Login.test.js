import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import Login from '@/Pages/Auth/Login.vue'
import { Passkeys } from '@laravel/passkeys'

const originalSecureContextDescriptor = Object.getOwnPropertyDescriptor(window, 'isSecureContext')

afterEach(() => {
    vi.restoreAllMocks()
    if (originalSecureContextDescriptor) {
        Object.defineProperty(window, 'isSecureContext', originalSecureContextDescriptor)
    } else {
        Reflect.deleteProperty(window, 'isSecureContext')
    }
})

const mountLogin = (props = {}) =>
    mount(Login, {
        global: {
            mocks: {
                $page: {
                    props: {
                        appName: 'Pfarrplaner',
                        appProvider: '',
                        dev: false,
                        demo: false,
                        errors: {},
                        ...props,
                    },
                },
            },
        },
    })

describe('Login', () => {
    it('offers passkey first, then the email field and email link without password recovery UI', () => {
        const wrapper = mountLogin()
        const email = wrapper.find('[name="email"]')
        const passkey = wrapper.find('[data-testid="passkey-login"]')
        const emailLink = wrapper.find('[data-testid="email-login-link"]')

        expect(email.exists()).toBe(true)
        expect(wrapper.text()).toContain('Melde dich einfach und sicher mit einem Passkey an.')
        expect(passkey.text()).toContain('Mit Passkey anmelden')
        expect(passkey.find('.fa-fingerprint').exists()).toBe(true)
        expect(emailLink.text()).toContain('Anmeldelink per E-Mail erhalten')
        expect(emailLink.find('.fa-envelope').exists()).toBe(true)
        expect(wrapper.text()).toContain('oder')
        expect(
            passkey.element.compareDocumentPosition(email.element) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy()
        expect(
            email.element.compareDocumentPosition(emailLink.element) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy()
        expect(passkey.element.parentElement.classList.contains('d-grid')).toBe(true)
        expect(wrapper.find('[name="password"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="forgot-password"]').exists()).toBe(false)
        expect(passkey.attributes('disabled')).toBeUndefined()
        wrapper.unmount()
    })

    it('shows server flash status and validation errors after returning to login', () => {
        const wrapper = mountLogin({
            errors: { login: 'Die Anmeldung ist abgelaufen.' },
            flash: { status: 'Der Anmeldelink ist ungültig oder abgelaufen.' },
        })

        expect(wrapper.find('[data-testid="login-status"]').text()).toContain(
            'Der Anmeldelink ist ungültig oder abgelaufen.',
        )
        expect(wrapper.find('[data-testid="login-error"]').text()).toContain(
            'Die Anmeldung ist abgelaufen.',
        )
    })

    it('focuses the email field on entry', async () => {
        const wrapper = mount(Login, {
            attachTo: document.body,
            global: {
                mocks: {
                    $page: {
                        props: {
                            appName: 'Pfarrplaner',
                            appProvider: '',
                            dev: false,
                            demo: false,
                            errors: {},
                        },
                    },
                },
            },
        })
        await nextTick()
        expect(wrapper.find('[name="email"]').element).toBe(document.activeElement)
        wrapper.unmount()
    })

    it('starts passkey login without exposing a password flow', async () => {
        const verify = vi.spyOn(Passkeys, 'verify').mockResolvedValue({})
        const wrapper = mountLogin()
        await wrapper.find('[name="email"]').setValue('person@example.org')
        await wrapper.find('[data-testid="passkey-login"]').trigger('click')

        expect(verify).toHaveBeenCalledOnce()
        expect(verify).toHaveBeenCalledWith({
            routes: {
                options: '/passkey.login-options',
                submit: '/passkey.login',
            },
            remember: expect.any(Function),
        })
        expect(verify.mock.calls[0][0].remember()).toBe(false)
    })

    it('fetches a fresh CSRF token before requesting an email link', async () => {
        window.axios.get.mockResolvedValueOnce({ data: { token: 'fresh-token' } })
        const wrapper = mountLogin()
        await wrapper.find('[name="email"]').setValue('person@example.org')
        await wrapper.find('[data-testid="email-login-link"]').trigger('click')

        expect(window.axios.get).toHaveBeenCalledWith('/csrf.keepalive')
        expect(wrapper.vm.$inertia.post).toHaveBeenCalledWith(
            '/login.link.request',
            { email: 'person@example.org', _token: 'fresh-token' },
            expect.objectContaining({
                onError: expect.any(Function),
                onFinish: expect.any(Function),
            }),
        )
    })

    it('explains how to finish email sign-in and lets the user change the address', async () => {
        const wrapper = mountLogin()
        await wrapper.find('[name="email"]').setValue('person@example.org')
        await wrapper.find('[data-testid="email-login-link"]').trigger('click')

        const [, , options] = wrapper.vm.$inertia.post.mock.calls.at(-1)
        options.onSuccess()
        await nextTick()

        const confirmation = wrapper.find('[data-testid="email-login-confirmation"]')
        expect(confirmation.attributes('role')).toBe('status')
        expect(confirmation.text()).toContain('Prüfe dein Postfach')
        expect(confirmation.text()).toContain('Öffne den Link in der Nachricht')
        expect(confirmation.text()).toContain('„Jetzt anmelden“')
        expect(wrapper.find('[name="email"]').exists()).toBe(false)

        await wrapper.find('[data-testid="different-email-address"]').trigger('click')

        expect(wrapper.find('[data-testid="email-login-confirmation"]').exists()).toBe(false)
        expect(wrapper.find('[name="email"]').element.value).toBe('')
        expect(wrapper.find('[data-testid="email-login-link"]').exists()).toBe(true)
    })

    it('explains when passkeys are unavailable in this browser', async () => {
        Object.defineProperty(window, 'isSecureContext', { configurable: true, value: true })
        vi.spyOn(Passkeys, 'verify').mockRejectedValueOnce({ name: 'NotSupportedError' })
        const wrapper = mountLogin()
        await wrapper.find('[data-testid="passkey-login"]').trigger('click')
        expect(wrapper.find('[role="alert"]').text()).toContain(
            'Dieser Browser oder dieses Gerät unterstützt die Passkey-Anmeldung nicht.',
        )
    })

    it('explains that passkeys require HTTPS outside localhost', async () => {
        Object.defineProperty(window, 'isSecureContext', { configurable: true, value: false })
        vi.spyOn(Passkeys, 'verify').mockRejectedValueOnce({ name: 'SecurityError' })
        const wrapper = mountLogin()
        await wrapper.find('[data-testid="passkey-login"]').trigger('click')
        expect(wrapper.find('[role="alert"]').text()).toContain(
            'Passkeys benötigen eine sichere Verbindung (HTTPS). Öffne Pfarrplaner über eine HTTPS-Adresse oder nutze den E-Mail-Link.',
        )
    })

    it('explains when the browser or device cancels passkey authentication', async () => {
        Object.defineProperty(window, 'isSecureContext', { configurable: true, value: true })
        vi.spyOn(Passkeys, 'verify').mockRejectedValueOnce({ name: 'UserCancelledError' })
        const wrapper = mountLogin()
        await wrapper.find('[data-testid="passkey-login"]').trigger('click')
        expect(wrapper.find('[role="alert"]').text()).toContain(
            'Der Browser oder Geräteschlüssel hat die Passkey-Anmeldung abgebrochen.',
        )
    })

    it('does not submit the legacy password endpoint when Enter submits the login form', async () => {
        const wrapper = mountLogin()
        await wrapper.find('form').trigger('submit')

        expect(window.axios.get).not.toHaveBeenCalled()
        expect(wrapper.vm.$inertia.post).not.toHaveBeenCalled()
    })
})

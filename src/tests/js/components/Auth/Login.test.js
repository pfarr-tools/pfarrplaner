import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import Login from '@/Pages/Auth/Login.vue'
import { Passkeys } from '@laravel/passkeys'

afterEach(() => vi.restoreAllMocks())

describe('Login', () => {
    it('shows only the continue action before the password step', () => {
        const wrapper = mount(Login, {
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

        expect(
            wrapper.findAll('button').filter((button) => button.text().trim() === 'Weiter'),
        ).toHaveLength(1)
        expect(
            wrapper.findAll('button').filter((button) => button.text().trim() === 'Anmelden'),
        ).toHaveLength(0)
        expect(wrapper.find('a[data-testid="forgot-password"]').exists()).toBe(true)
    })

    it('focuses the email field and then the password field as login advances', async () => {
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

        expect(document.activeElement).toBe(wrapper.find('[name="email"]').element)

        await wrapper.find('[name="email"]').setValue('person@example.org')
        await wrapper.find('[data-testid="continue-login"]').trigger('click')
        await nextTick()

        expect(document.activeElement).toBe(wrapper.find('[name="password"]').element)
        wrapper.unmount()
    })

    it('shows the same password step locally after the username, without a request', async () => {
        const wrapper = mount(Login, {
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

        await wrapper.find('[name="email"]').setValue('person@example.org')
        await wrapper.find('[data-testid="continue-login"]').trigger('click')

        expect(wrapper.find('input[type="hidden"][name="email"]').exists()).toBe(true)
        expect(wrapper.find('[name="password"]').exists()).toBe(true)
        expect(new window.FormData(wrapper.find('form').element).get('email')).toBe(
            'person@example.org',
        )
        expect(wrapper.text()).toContain('person@example.org')
        expect(wrapper.text()).toContain('Passwort')
        expect(
            wrapper.findAll('button').filter((button) => button.text().trim() === 'Weiter'),
        ).toHaveLength(0)
        expect(
            wrapper.findAll('button').filter((button) => button.text().trim() === 'Anmelden'),
        ).toHaveLength(1)
        expect(window.axios.get).not.toHaveBeenCalled()
        expect(wrapper.vm.$inertia.post).not.toHaveBeenCalled()
    })

    it('offers discoverable passkey login before the account is identified', async () => {
        const verify = vi.spyOn(Passkeys, 'verify').mockResolvedValue({})
        const wrapper = mount(Login, {
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

        expect(wrapper.find('[name="email"]').exists()).toBe(true)
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

    it('keeps password and passkey actions together in one responsive action row', async () => {
        const wrapper = mount(Login, {
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
        await wrapper.find('[name="email"]').setValue('person@example.org')
        await wrapper.find('[data-testid="continue-login"]').trigger('click')

        const loginButton = wrapper
            .findAll('button')
            .find((button) => button.text().trim() === 'Anmelden')
        const passkeyButton = wrapper.find('[data-testid="passkey-login"]')
        expect(loginButton.element.parentElement).toBe(passkeyButton.element.parentElement)
        expect(loginButton.element.parentElement.classList.contains('d-flex')).toBe(true)
    })
})

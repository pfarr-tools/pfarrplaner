import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import Login from '@/Pages/Auth/Login.vue'

describe('Login', () => {
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
        expect(window.axios.get).not.toHaveBeenCalled()
        expect(wrapper.vm.$inertia.post).not.toHaveBeenCalled()
    })
})

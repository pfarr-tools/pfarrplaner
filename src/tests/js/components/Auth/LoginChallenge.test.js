import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import LoginChallenge from '@/Pages/Auth/LoginChallenge.vue'

describe('LoginChallenge', () => {
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
        await wrapper.find('#code').setValue('123456')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.vm.$inertia.post).toHaveBeenCalledWith(
            '/login.challenge.verify',
            { code: '123456' },
            expect.objectContaining({ preserveState: true }),
        )
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
        expect(vi.mocked(route)).toHaveBeenCalled()
    })
})

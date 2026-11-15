import { flushPromises, mount } from '@vue/test-utils'
import axios from 'axios'
import { describe, expect, it, vi } from 'vitest'
import SecurityTab from '@/components/Profile/Tabs/SecurityTab.vue'

/** Mount the profile security panel with the required profile props. */
const mountSecurityTab = (props = {}) =>
    mount(SecurityTab, {
        props: {
            user: { id: 1 },
            password: { current: '', new: '', confirm: '' },
            ...props,
        },
        global: {
            stubs: {
                'form-input': {
                    template: '<input :name="name" />',
                    props: ['name'],
                },
            },
        },
    })

describe('SecurityTab', () => {
    it('offers TOTP setup for an unconfigured account', () => {
        const wrapper = mountSecurityTab()

        expect(wrapper.text()).toContain('Zwei-Faktor-Authentifizierung einrichten')
        expect(wrapper.find('[data-dusk="two-factor-setup"]').exists()).toBe(false)
    })

    it('shows the disable action for an enabled account', () => {
        const wrapper = mountSecurityTab({ twoFactorEnabled: true })

        expect(wrapper.text()).toContain('Zwei-Faktor-Authentifizierung deaktivieren')
        expect(wrapper.text()).not.toContain('Zwei-Faktor-Authentifizierung einrichten')
    })

    it('renders recovery codes only after setup data is received', async () => {
        const wrapper = mountSecurityTab()
        wrapper.vm.setup = {
            provisioning_uri: 'otpauth://totp/Pfarrplaner:test@example.com?secret=JBSWY3DPEHPK3PXP',
            recovery_codes: ['first-code', 'second-code'],
        }
        await wrapper.vm.$nextTick()

        expect(wrapper.find('[data-dusk="two-factor-setup"]').exists()).toBe(true)
        expect(wrapper.text()).toContain('first-code')
        expect(wrapper.text()).toContain('second-code')
        expect(wrapper.text()).toContain('JBSWY3DPEHPK3PXP')
    })

    it('starts TOTP setup and confirms it with fresh password data', async () => {
        const post = vi.spyOn(axios, 'post')
        post.mockResolvedValueOnce({
            data: {
                provisioning_uri:
                    'otpauth://totp/Pfarrplaner:test@example.com?secret=JBSWY3DPEHPK3PXP',
                recovery_codes: ['one-time-code'],
            },
        })
        post.mockResolvedValueOnce({ data: { enabled: true } })
        const wrapper = mountSecurityTab()
        wrapper.vm.twoFactor.currentPassword = 'Current1!'

        await wrapper.find('button').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(1, '/user.two-factor.start', {
            current_password: 'Current1!',
        })
        expect(wrapper.text()).toContain('one-time-code')
        expect(wrapper.vm.twoFactor.currentPassword).toBe('')

        wrapper.vm.twoFactor.code = '123 456'
        wrapper.vm.twoFactor.currentPassword = 'Current1!'
        await wrapper.find('.btn-primary').trigger('click')
        await flushPromises()

        expect(post).toHaveBeenNthCalledWith(2, '/user.two-factor.confirm', {
            current_password: 'Current1!',
            code: '123 456',
        })
        expect(wrapper.emitted('two-factor-enabled')).toHaveLength(1)
        expect(wrapper.text()).toContain('Die Zwei-Faktor-Authentifizierung ist aktiviert')
        expect(wrapper.find('[data-dusk="two-factor-setup"]').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('one-time-code')
        expect(wrapper.text()).not.toContain('JBSWY3DPEHPK3PXP')
        expect(wrapper.vm.twoFactor.currentPassword).toBe('')
        post.mockRestore()
    })
})

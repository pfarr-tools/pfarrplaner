import { mount } from '@vue/test-utils'
import axios from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import ManualLoginLinkControl from '@/Pages/Admin/User/ManualLoginLinkControl.vue'

const originalClipboard = Object.getOwnPropertyDescriptor(globalThis.navigator, 'clipboard')

beforeEach(() => {
    vi.stubGlobal('route', (_name, userId) => `/admin/benutzer/${userId}/login-link`)
    Object.defineProperty(globalThis.navigator, 'clipboard', {
        configurable: true,
        value: { writeText: vi.fn().mockResolvedValue() },
    })
})

afterEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
    if (originalClipboard) {
        Object.defineProperty(globalThis.navigator, 'clipboard', originalClipboard)
    } else {
        delete globalThis.navigator.clipboard
    }
})

describe('ManualLoginLinkControl', () => {
    it('creates and displays the returned URL and expiration time', async () => {
        const url = 'https://pfarrplaner.example/login/link/0123456789abcdef'
        const post = vi.spyOn(axios, 'post').mockResolvedValue({
            data: { manual_login_url: url, expires_at: '2026-11-20T11:00:00.000000Z' },
        })
        const wrapper = mount(ManualLoginLinkControl, { props: { userId: 42 } })

        await wrapper.find('[data-testid="generate-manual-login-link"]').trigger('click')

        expect(post).toHaveBeenCalledWith('/admin/benutzer/42/login-link', {})
        expect(wrapper.find('[data-testid="manual-login-url"]').element.value).toBe(url)
        expect(wrapper.text()).toContain('Gültig bis')
        expect(wrapper.text()).toContain('60 Minuten')
    })

    it('copies the generated URL and confirms the copy', async () => {
        const url = 'https://pfarrplaner.example/login/link/0123456789abcdef'
        vi.spyOn(axios, 'post').mockResolvedValue({
            data: { manual_login_url: url, expires_at: '2026-11-20T11:00:00.000000Z' },
        })
        const wrapper = mount(ManualLoginLinkControl, { props: { userId: 42 } })
        await wrapper.find('[data-testid="generate-manual-login-link"]').trigger('click')
        await wrapper.find('[data-testid="copy-manual-login-link"]').trigger('click')

        expect(globalThis.navigator.clipboard.writeText).toHaveBeenCalledWith(url)
        expect(wrapper.text()).toContain('Anmeldelink wurde kopiert.')
    })

    it('reports an understandable error when link creation fails', async () => {
        vi.spyOn(axios, 'post').mockRejectedValue(new Error('Request failed'))
        const wrapper = mount(ManualLoginLinkControl, { props: { userId: 42 } })

        await wrapper.find('[data-testid="generate-manual-login-link"]').trigger('click')

        expect(wrapper.find('[role="alert"]').text()).toContain(
            'Anmeldelink konnte nicht erstellt werden.',
        )
    })

    it('selects the link when the browser clipboard is unavailable', async () => {
        const url = 'https://pfarrplaner.example/login/link/0123456789abcdef'
        vi.spyOn(axios, 'post').mockResolvedValue({
            data: { manual_login_url: url, expires_at: '2026-11-20T11:00:00.000000Z' },
        })
        globalThis.navigator.clipboard.writeText.mockRejectedValue(
            new Error('Clipboard permission denied'),
        )
        const select = vi.spyOn(globalThis.HTMLInputElement.prototype, 'select')
        const wrapper = mount(ManualLoginLinkControl, { props: { userId: 42 } })
        await wrapper.find('[data-testid="generate-manual-login-link"]').trigger('click')
        await wrapper.find('[data-testid="copy-manual-login-link"]').trigger('click')

        expect(select).toHaveBeenCalledOnce()
        expect(wrapper.text()).toContain('Der Link ist markiert.')
    })
})

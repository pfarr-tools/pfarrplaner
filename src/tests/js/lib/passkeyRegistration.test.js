import { afterEach, describe, expect, it, vi } from 'vitest'
import axios from 'axios'
import { startRegistration } from '@simplewebauthn/browser'
import { PasskeyRegistration } from '@/lib/passkeyRegistration'

vi.mock('@simplewebauthn/browser', () => ({ startRegistration: vi.fn() }))

afterEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
})

const routes = { options: '/options', submit: '/store', diagnostics: '/diagnostics' }

/** Provide browser capabilities without requiring a physical authenticator. */
function browser() {
    vi.stubGlobal('isSecureContext', true)
    vi.stubGlobal('PublicKeyCredential', class {})
}

describe('passkey registration', () => {
    it('passes unchanged options to the authenticator and stores its response', async () => {
        browser()
        const options = { authenticatorSelection: { userVerification: 'required' } }
        vi.spyOn(axios, 'get').mockResolvedValue({ data: { options } })
        vi.mocked(startRegistration).mockResolvedValue({ id: 'credential' })
        const post = vi.spyOn(axios, 'post').mockResolvedValue({ data: { id: 1 } })
        await expect(PasskeyRegistration.register({ name: 'Laptop', routes })).resolves.toEqual({
            id: 1,
        })
        expect(startRegistration).toHaveBeenCalledWith({ optionsJSON: options })
        expect(post).toHaveBeenCalledWith(
            '/store',
            { name: 'Laptop', credential: { id: 'credential' } },
            expect.any(Object),
        )
    })

    it.each([
        ['NotAllowedError', 'abgebrochen'],
        ['InvalidStateError', 'bereits'],
        ['NotSupportedError', 'unterstützt'],
        ['SecurityError', 'Webadresse'],
        ['UnknownError', 'Passkey-Anbieter'],
        ['ConstraintError', 'PIN oder Biometrie'],
    ])('explains %s without sending raw error messages', async (name, expected) => {
        browser()
        vi.spyOn(axios, 'get').mockResolvedValue({ data: { options: {} } })
        vi.mocked(startRegistration).mockRejectedValue(Object.assign(new Error('SECRET'), { name }))
        const post = vi.spyOn(axios, 'post').mockResolvedValue({})
        await expect(PasskeyRegistration.register({ name: 'SECRET', routes })).rejects.toThrow(
            expected,
        )
        const report = post.mock.calls[0][1]
        expect(report).toMatchObject({ phase: 'authenticator', error: name })
        expect(JSON.stringify(report)).not.toContain('SECRET')
        expect(post.mock.calls[0][0]).toBe('/diagnostics')
    })

    it('distinguishes preparation and server validation failures and permits retries', async () => {
        browser()
        const get = vi.spyOn(axios, 'get').mockRejectedValueOnce({ response: { status: 419 } })
        get.mockResolvedValue({ data: { options: {} } })
        vi.mocked(startRegistration).mockResolvedValue({ id: 'credential' })
        const post = vi.spyOn(axios, 'post').mockImplementation(async (url) => {
            if (url === '/store') throw { response: { status: 422 } }
            return {}
        })
        await expect(PasskeyRegistration.register({ name: 'Laptop', routes })).rejects.toThrow(
            'Vorbereitung',
        )
        await expect(PasskeyRegistration.register({ name: 'Laptop', routes })).rejects.toThrow(
            'Serverprüfung',
        )
        expect(
            post.mock.calls.filter(([url]) => url === '/diagnostics').map(([, data]) => data.phase),
        ).toEqual(['options', 'store'])
        expect(get.mock.calls[0][1].headers['X-Passkey-Request-ID']).not.toBe(
            get.mock.calls[1][1].headers['X-Passkey-Request-ID'],
        )
    })

    it('reports HTTPS requirements even when diagnostic delivery fails', async () => {
        vi.stubGlobal('isSecureContext', false)
        vi.spyOn(window.crypto, 'randomUUID').mockImplementation(() => {
            throw new Error('Unavailable outside HTTPS')
        })
        vi.spyOn(axios, 'post').mockRejectedValue(new Error('offline'))
        await expect(PasskeyRegistration.register({ name: 'Laptop', routes })).rejects.toThrow(
            'HTTPS',
        )
    })
})

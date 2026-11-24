import axios from 'axios'
import { startRegistration } from '@simplewebauthn/browser'

const errors = [
    'NotAllowedError',
    'InvalidStateError',
    'NotSupportedError',
    'SecurityError',
    'ConstraintError',
    'UnknownError',
    'AbortError',
    'TypeError',
]

/** Reduce browser identification to families; never transmit the raw user agent. */
function environment() {
    const agent = window.navigator.userAgent
    const browser = /Edg\//.test(agent)
        ? 'Edge'
        : /Firefox\//.test(agent)
          ? 'Firefox'
          : /Chrome\//.test(agent)
            ? 'Chrome'
            : /Safari\//.test(agent)
              ? 'Safari'
              : 'Other'
    const platform = /Android/.test(agent)
        ? 'Android'
        : /iPhone|iPad/.test(agent)
          ? 'iOS'
          : /Windows/.test(agent)
            ? 'Windows'
            : /Macintosh/.test(agent)
              ? 'macOS'
              : /Linux/.test(agent)
                ? 'Linux'
                : 'Other'
    return { browser, platform }
}

/** Explain a failed registration step without exposing provider or server payloads. */
function failureMessage(phase, name, status) {
    if (!window.isSecureContext) return 'Passkeys benötigen eine sichere Verbindung (HTTPS).'
    if (status === 401 || status === 419) return 'Die Sitzung ist abgelaufen. Melde dich erneut an.'
    if (status === 429) return 'Zu viele Versuche. Bitte warte eine Minute und versuche es erneut.'
    if (phase === 'options')
        return 'Die Vorbereitung ist fehlgeschlagen. Prüfe die Verbindung und lade die Seite neu.'
    if (phase === 'store')
        return 'Die Serverprüfung konnte nicht abgeschlossen werden. Starte die Einrichtung erneut. Falls der Fehler bleibt, wende dich mit der Diagnose-ID an die Administration.'
    switch (name) {
        case 'NotAllowedError':
        case 'AbortError':
            return 'Die Einrichtung wurde abgebrochen, nicht erlaubt oder hat zu lange gedauert. Starte erneut und bestätige die Geräteabfrage zeitnah. Prüfe gegebenenfalls den Passkey-Anbieter in den Geräteeinstellungen.'
        case 'InvalidStateError':
            return 'Dieser Passkey ist bereits eingerichtet. Verwende ihn zur Anmeldung oder wähle einen anderen Speicherort.'
        case 'NotSupportedError':
        case 'ConstraintError':
            return 'Der Browser oder Passkey-Anbieter unterstützt die benötigten Passkeys mit PIN oder Biometrie nicht. Prüfe die Bildschirmsperre und den Passkey-Anbieter oder versuche einen anderen aktuellen Browser bzw. FIDO2-Schlüssel.'
        case 'SecurityError':
            return 'Passkeys sind für diese Webadresse nicht verfügbar. Öffne die gewohnte HTTPS-Adresse. Falls der Fehler bleibt, muss die Administration die Passkey-Domain prüfen.'
        default:
            return 'Der Browser oder Passkey-Anbieter konnte den Passkey nicht erstellen. Prüfe dessen Einrichtung und die Bildschirmsperre. Versuche unter „Auf andere Weise speichern“ einen anderen Anbieter oder ein anderes Gerät.'
    }
}

/**
 * Register with the existing WebAuthn adapter, retaining the phase of failures.
 * Diagnostic delivery is best effort and never delays a retry or includes credentials.
 *
 * @param {object} settings Name and application endpoint URLs.
 * @returns {Promise<object>} Stored passkey's safe management fields.
 */
async function register({ name, routes }) {
    const bytes = window.crypto.getRandomValues(new Uint8Array(16))
    bytes[6] = (bytes[6] & 0x0f) | 0x40
    bytes[8] = (bytes[8] & 0x3f) | 0x80
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
    const correlationId = `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
    const config = { headers: { 'X-Passkey-Request-ID': correlationId } }
    let phase = 'options'
    try {
        if (!window.isSecureContext || !window.PublicKeyCredential) {
            phase = 'authenticator'
            throw new window.DOMException('Unavailable', 'NotSupportedError')
        }
        const { data } = await axios.get(routes.options, config)
        phase = 'authenticator'
        const credential = await startRegistration({ optionsJSON: data.options })
        phase = 'store'
        const response = await axios.post(routes.submit, { name, credential }, config)
        return response.data
    } catch (error) {
        const errorName = errors.includes(error?.name) ? error.name : 'Other'
        const status = error?.response?.status || null
        const diagnosis = {
            correlation_id: correlationId,
            phase,
            error: errorName,
            status,
            ...environment(),
        }
        void axios.post(routes.diagnostics, diagnosis, { timeout: 5000 }).catch(() => {})
        const label = {
            options: 'Vorbereitung',
            authenticator: 'Gerät/Browser',
            store: 'Serverprüfung',
        }[phase]
        throw new Error(
            `${label}: ${failureMessage(phase, errorName, status)} Du kannst dich weiterhin per E-Mail-Link anmelden. Diagnose-ID: ${correlationId}`,
        )
    }
}

/** Shared registration flow for the profile and security onboarding. */
export const PasskeyRegistration = { register }

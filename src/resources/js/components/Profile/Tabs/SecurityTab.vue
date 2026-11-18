<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -
  -
  - Pfarrplaner is based on the Laravel framework (https://laravel.com).
  - This file may contain code created by Laravel's scaffolding functions.
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="security-tab">
        <h3>Zwei-Faktor-Authentifizierung</h3>
        <p v-if="factorEnabled">Die Zwei-Faktor-Authentifizierung ist aktiviert.</p>
        <p v-else-if="factorPending">
            Die Einrichtung wurde begonnen. Bestätigen Sie sie mit einem Code aus Ihrer
            Authenticator-App.
        </p>
        <p v-else>Schützen Sie Ihr Konto zusätzlich mit einer Authenticator-App.</p>

        <button
            v-if="!factorEnabled"
            type="button"
            class="btn btn-secondary"
            :disabled="twoFactor.busy"
            ref="setupTrigger"
            data-testid="open-two-factor-setup"
            @click="openSetup"
        >
            {{
                factorPending
                    ? 'Einrichtung neu starten'
                    : 'Zwei-Faktor-Authentifizierung einrichten'
            }}
        </button>
        <button
            v-if="factorEnabled"
            type="button"
            class="btn btn-outline-danger"
            :disabled="twoFactor.busy"
            data-testid="disable-two-factor"
            @click="disableTwoFactor"
        >
            Zwei-Faktor-Authentifizierung deaktivieren
        </button>

        <hr />
        <section
            class="passkey-management"
            data-testid="passkey-management"
        >
            <h3>Passkeys</h3>
            <p>Mit Passkeys melden Sie sich sicher mit der Geräte-PIN oder Biometrie an.</p>
            <label for="passkey-name">Name für dieses Gerät</label>
            <input
                id="passkey-name"
                v-model="newPasskeyName"
                class="form-control mb-2"
                maxlength="255"
                autocomplete="off"
                data-testid="passkey-name"
            />
            <button
                type="button"
                class="btn btn-secondary"
                ref="registerPasskeyTrigger"
                data-testid="register-passkey"
                :disabled="passkeyBusy || !newPasskeyName.trim()"
                @click="registerPasskey"
            >
                {{ passkeyBusy ? 'Passkey wird eingerichtet …' : 'Passkey einrichten' }}
            </button>
            <p
                v-if="passkeyError"
                class="alert mt-2"
                :class="passkeyError ? 'alert-danger' : 'alert-info'"
                role="alert"
            >
                {{ passkeyError }}
            </p>
            <ul
                v-if="managedPasskeys.length"
                class="list-group mt-3"
            >
                <li
                    v-for="passkey in managedPasskeys"
                    :key="passkey.id"
                    class="list-group-item"
                >
                    <label :for="`passkey-label-${passkey.id}`">Gerätename</label>
                    <input
                        :id="`passkey-label-${passkey.id}`"
                        v-model="passkey.name"
                        class="form-control mb-2"
                        maxlength="255"
                        :aria-label="`Name des Passkeys ${passkey.name}`"
                    />
                    <small>{{ passkey.authenticator || 'Passkey' }}</small>
                    <div class="mt-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary me-2"
                            data-testid="rename-passkey"
                            :disabled="passkeyBusy"
                            @click="renamePasskey(passkey)"
                        >
                            Umbenennen
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            data-testid="revoke-passkey"
                            :disabled="passkeyBusy"
                            @click="revokePasskey(passkey)"
                        >
                            Widerrufen
                        </button>
                    </div>
                </li>
            </ul>
            <p
                v-else
                class="text-muted mt-3"
            >
                Noch kein Passkey eingerichtet.
            </p>
        </section>

        <section
            v-if="setupModalOpen"
            class="setup-backdrop"
            data-dusk="two-factor-setup"
            @click.self="closeSetup"
        >
            <div
                class="setup-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="two-factor-setup-title"
                tabindex="-1"
                @keydown.esc.stop.prevent="closeSetup"
                @keydown.tab="trapSetupFocus"
            >
                <div class="setup-dialog-heading">
                    <h4 id="two-factor-setup-title">Authenticator-App einrichten</h4>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Einrichtung schließen"
                        data-testid="two-factor-setup-close"
                        :disabled="twoFactor.busy"
                        @click="closeSetup"
                    />
                </div>
                <div
                    v-if="twoFactor.error"
                    class="alert alert-danger mt-3"
                    role="alert"
                >
                    {{ twoFactor.error }}
                </div>
                <div v-if="setupStep === 1">
                    <p>
                        In wenigen Schritten richten Sie eine zusätzliche Anmeldung mit einer
                        Authenticator-App ein.
                    </p>
                    <p>
                        Geeignete Apps sind zum Beispiel Microsoft Authenticator oder die
                        quelloffene App 2FAS Auth.
                    </p>
                </div>
                <div
                    v-else-if="setupStep === 2 && setup"
                    class="two-factor-setup"
                >
                    <p>
                        Scannen Sie diesen QR-Code mit Microsoft Authenticator, 2FAS Auth oder einer
                        anderen TOTP-App.
                    </p>
                    <p>
                        Konto: <strong>{{ user.email }}</strong>
                    </p>
                    <img
                        v-if="qrCode"
                        :src="qrCode"
                        alt="QR-Code für die Authenticator-App"
                        data-testid="two-factor-qr"
                    />
                    <div
                        class="mt-3"
                        data-testid="two-factor-manual-key"
                    >
                        <h5>Einrichtungsschlüssel für die manuelle Eingabe</h5>
                        <p>
                            Einige Apps, zum Beispiel Bitwarden, benötigen diesen Schlüssel. Geben
                            Sie ihn dort ein, wenn die App danach fragt:
                        </p>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <code class="d-block text-break">{{ provisioningSecret }}</code>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-testid="copy-totp-manual-key"
                                aria-label="Einrichtungsschlüssel kopieren"
                                @click="copyToClipboard(provisioningSecret, 'manual-key')"
                            >
                                {{ copiedField === 'manual-key' ? 'Kopiert' : 'Kopieren' }}
                            </button>
                        </div>
                    </div>
                    <h5>Recovery-Codes</h5>
                    <p>
                        Bewahren Sie diese Codes sicher auf. Jeder Code kann nur einmal verwendet
                        werden.
                    </p>
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <textarea
                            class="form-control font-monospace"
                            rows="6"
                            readonly
                            spellcheck="false"
                            aria-label="Recovery-Codes"
                            data-testid="recovery-codes"
                            :value="recoveryCodesText"
                        />
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-testid="copy-recovery-codes"
                            aria-label="Alle Recovery-Codes kopieren"
                            @click="copyToClipboard(recoveryCodesText, 'recovery-codes')"
                        >
                            {{ copiedField === 'recovery-codes' ? 'Kopiert' : 'Kopieren' }}
                        </button>
                    </div>
                    <p
                        v-if="clipboardError"
                        class="text-danger mt-2"
                        role="alert"
                    >
                        {{ clipboardError }}
                    </p>
                    <p
                        class="visually-hidden"
                        role="status"
                        aria-live="polite"
                    >
                        {{
                            copiedField === 'manual-key'
                                ? 'Einrichtungsschlüssel kopiert.'
                                : copiedField === 'recovery-codes'
                                  ? 'Alle Recovery-Codes kopiert.'
                                  : ''
                        }}
                    </p>
                    <label
                        ><input
                            v-model="recoveryCodesSaved"
                            type="checkbox"
                            data-testid="recovery-codes-saved"
                        />
                        Ich habe die Recovery-Codes sicher gespeichert.</label
                    >
                </div>
                <form
                    id="two-factor-confirm-form"
                    v-else-if="setupStep === 3"
                    @submit.prevent="confirmTwoFactor"
                >
                    <p>
                        Geben Sie einen aktuellen sechsstelligen Code aus Ihrer Authenticator-App
                        ein, um die Einrichtung abzuschließen.
                    </p>
                    <OneTimeCodeInput
                        v-model="twoFactor.code"
                        label="Sechsstelliger Code"
                        name="two_factor_code"
                        test-id-prefix="totp"
                    />
                </form>
                <div
                    class="setup-dialog-actions d-flex flex-wrap gap-2 mt-3"
                    data-testid="two-factor-setup-actions"
                >
                    <button
                        v-if="setupStep === 1"
                        type="button"
                        class="btn btn-primary"
                        data-testid="two-factor-intro-next"
                        :disabled="twoFactor.busy"
                        @click="startTwoFactor"
                    >
                        Weiter
                    </button>
                    <button
                        v-else-if="setupStep === 2"
                        type="button"
                        class="btn btn-primary"
                        data-testid="two-factor-setup-next"
                        :disabled="!recoveryCodesSaved"
                        @click="setupStep = 3"
                    >
                        Weiter
                    </button>
                    <button
                        v-else-if="setupStep === 3"
                        type="submit"
                        form="two-factor-confirm-form"
                        class="btn btn-primary"
                        data-testid="two-factor-confirm"
                        :disabled="twoFactor.busy || twoFactor.code.length !== 6"
                    >
                        Einrichtung bestätigen
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-testid="two-factor-setup-cancel"
                        :disabled="twoFactor.busy"
                        @click="closeSetup"
                    >
                        Abbrechen
                    </button>
                </div>
            </div>
        </section>
        <div
            v-if="twoFactor.error && !setupModalOpen"
            class="alert alert-danger mt-3"
            role="alert"
        >
            {{ twoFactor.error }}
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import QRCode from 'qrcode'
import OneTimeCodeInput from '../../Ui/forms/OneTimeCodeInput.vue'
import { Passkeys } from '@laravel/passkeys'
export default {
    name: 'SecurityTab',
    components: { OneTimeCodeInput },
    props: {
        user: { type: Object, required: true },
        twoFactorEnabled: { type: Boolean, default: false },
        twoFactorPending: { type: Boolean, default: false },
        passkeys: { type: Array, default: () => [] },
    },
    emits: ['two-factor-enabled', 'two-factor-disabled'],
    data() {
        return {
            setup: null,
            qrCode: '',
            setupStep: 1,
            setupModalOpen: false,
            recoveryCodesSaved: false,
            copiedField: '',
            clipboardError: '',
            factorEnabled: this.twoFactorEnabled,
            factorPending: this.twoFactorPending,
            managedPasskeys: this.passkeys.map((passkey) => ({ ...passkey })),
            newPasskeyName: '',
            passkeyBusy: false,
            passkeyError: '',
            twoFactor: {
                busy: false,
                code: '',
                error: '',
            },
        }
    },
    computed: {
        /** Read the TOTP key locally from the provisioning URI. */
        provisioningSecret() {
            return new window.URL(this.setup.provisioning_uri).searchParams.get('secret') || ''
        },
        /** Return recovery codes as newline-separated text for display and copying. */
        recoveryCodesText() {
            return (this.setup?.recovery_codes || []).join('\n')
        },
    },
    methods: {
        /** Copy setup data and report clipboard success or failure to the user. */
        async copyToClipboard(value, field) {
            this.clipboardError = ''
            this.copiedField = ''

            try {
                await navigator.clipboard.writeText(value)
                this.copiedField = field
            } catch {
                this.clipboardError =
                    'Der Inhalt konnte nicht kopiert werden. Bitte markieren Sie ihn und kopieren Sie ihn manuell.'
            }
        },
        /** Register a discoverable passkey in the authenticated session. */
        async registerPasskey() {
            if (this.passkeyBusy || !this.newPasskeyName.trim()) return

            this.passkeyBusy = true
            this.passkeyError = ''
            try {
                const result = await Passkeys.register({
                    name: this.newPasskeyName.trim(),
                    routes: {
                        options: window.route('user.passkeys.options'),
                        submit: window.route('user.passkeys.store'),
                    },
                })
                this.managedPasskeys.unshift({ ...result, authenticator: 'Passkey' })
                this.newPasskeyName = ''
            } catch (error) {
                this.passkeyError =
                    'Passkey konnte nicht eingerichtet werden. Bitte erneut versuchen.'
            } finally {
                this.passkeyBusy = false
            }
        },
        /** Persist a changed display name for one owned passkey. */
        async renamePasskey(passkey) {
            await this.runPasskeyManagement(async () => {
                const response = await axios.patch(
                    window.route('user.passkeys.rename', passkey.id),
                    {
                        name: passkey.name,
                    },
                )
                passkey.name = response.data.name
            })
        },
        /** Revoke one passkey after explicit confirmation. */
        async revokePasskey(passkey) {
            if (!window.confirm(`Passkey „${passkey.name}“ wirklich widerrufen?`)) return

            await this.runPasskeyManagement(async () => {
                await axios.delete(window.route('user.passkeys.destroy', passkey.id))
                this.managedPasskeys = this.managedPasskeys.filter((item) => item.id !== passkey.id)
            })
        },
        /** Run an authenticated passkey mutation and report failures. */
        async runPasskeyManagement(action) {
            this.passkeyBusy = true
            this.passkeyError = ''
            try {
                await action()
            } catch (error) {
                this.passkeyError = 'Passkey-Verwaltung nicht möglich. Bitte erneut versuchen.'
            } finally {
                this.passkeyBusy = false
            }
        },
        /** Open and reset the guided enrollment dialog. */
        openSetup() {
            this.setup = null
            this.qrCode = ''
            this.setupStep = 1
            this.recoveryCodesSaved = false
            this.copiedField = ''
            this.clipboardError = ''
            this.twoFactor.error = ''
            this.twoFactor.code = ''
            this.setupModalOpen = true
            this.$nextTick(() => this.$el.querySelector('.setup-dialog')?.focus())
        },
        /** Clear transient secrets when the enrollment dialog is closed. */
        closeSetup() {
            if (this.twoFactor.busy) return

            this.setupModalOpen = false
            this.setup = null
            this.qrCode = ''
            this.recoveryCodesSaved = false
            this.copiedField = ''
            this.clipboardError = ''
            this.twoFactor.error = ''
            this.twoFactor.code = ''
            this.$nextTick(() => this.$refs.setupTrigger?.focus())
        },
        /** Keep keyboard focus inside the guided setup dialog. */
        trapSetupFocus(event) {
            const dialog = event.currentTarget
            const focusable = Array.from(
                dialog.querySelectorAll(
                    'button:not([disabled]), input:not([disabled]):not([type="hidden"]), summary, [tabindex]:not([tabindex="-1"])',
                ),
            )
            if (!focusable.length) {
                event.preventDefault()
                return
            }

            const first = focusable[0]
            const last = focusable[focusable.length - 1]
            if (
                event.shiftKey &&
                (document.activeElement === first || document.activeElement === dialog)
            ) {
                event.preventDefault()
                last.focus()
            } else if (
                !event.shiftKey &&
                (document.activeElement === last || document.activeElement === dialog)
            ) {
                event.preventDefault()
                first.focus()
            }
        },
        /** Start TOTP enrollment for the authenticated account. */
        async startTwoFactor() {
            if (this.twoFactor.busy) return

            await this.runTwoFactorRequest(
                () => axios.post(window.route('user.two-factor.start'), {}),
                async (response) => {
                    this.setup = response.data
                    this.qrCode = await QRCode.toDataURL(response.data.provisioning_uri)
                    this.factorPending = true
                    this.setupStep = 2
                },
            )
        },
        /** Confirm the code from the authenticator application. */
        async confirmTwoFactor() {
            await this.runTwoFactorRequest(
                () =>
                    axios.post(window.route('user.two-factor.confirm'), {
                        code: this.twoFactor.code,
                    }),
                () => {
                    this.factorEnabled = true
                    this.factorPending = false
                    this.setup = null
                    this.setupModalOpen = false
                    this.qrCode = ''
                    this.recoveryCodesSaved = false
                    this.twoFactor.code = ''
                    this.$emit('two-factor-enabled')
                    this.twoFactor.error = ''
                    this.$nextTick(() => this.$refs.setupTrigger?.focus())
                },
            )
        },
        /** Disable TOTP after explicit confirmation. */
        async disableTwoFactor() {
            if (!window.confirm('Zwei-Faktor-Authentifizierung wirklich deaktivieren?')) return
            await this.runTwoFactorRequest(
                () => axios.post(window.route('user.two-factor.disable'), {}),
                () => {
                    this.factorEnabled = false
                    this.factorPending = false
                    this.setup = null
                    this.$emit('two-factor-disabled')
                },
            )
        },
        /** Execute a TOTP request and normalize its generic error responses. */
        async runTwoFactorRequest(request, onSuccess) {
            this.twoFactor.busy = true
            this.twoFactor.error = ''

            try {
                const response = await request()
                await onSuccess(response)
            } catch (error) {
                this.twoFactor.error =
                    error.response?.status === 429
                        ? 'Zu viele Versuche. Bitte später erneut versuchen.'
                        : error.response?.data?.errors?.code?.[0] ||
                          'Die Zwei-Faktor-Aktion konnte nicht ausgeführt werden.'
            } finally {
                this.twoFactor.busy = false
            }
        },
    },
}
</script>

<style scoped>
.setup-backdrop {
    position: fixed;
    z-index: 1050;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / 55%);
}

.setup-dialog {
    width: min(100%, 38rem);
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.5rem;
    border-radius: 0.5rem;
    background: var(--bs-body-bg, #fff);
}

.setup-dialog-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.setup-dialog img {
    display: block;
    width: min(100%, 18rem);
    margin: 1rem auto;
}
</style>

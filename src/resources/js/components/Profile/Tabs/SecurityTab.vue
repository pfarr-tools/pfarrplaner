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
        <p class="password-requirements">
            Das Passwort muss mindestens 8 Zeichen lang sein und Groß- und Kleinbuchstaben, eine
            Zahl sowie ein Sonderzeichen enthalten.
        </p>
        <FormInput
            name="current_password"
            label="Aktuelles Passwort"
            help="Um dein Passwort zu ändern, musst du dein aktuelles Passwort mit angeben."
            type="password"
            autocomplete="current-password"
            :model-value="password.current"
            @update:model-value="updatePassword('current', $event)"
        />
        <FormInput
            name="new_password"
            label="Neues Passwort"
            type="password"
            autocomplete="new-password"
            :model-value="password.new"
            @update:model-value="updatePassword('new', $event)"
        />
        <FormInput
            name="new_password_confirm"
            label="Neues Passwort wiederholen"
            type="password"
            autocomplete="new-password"
            :model-value="password.confirm"
            @update:model-value="updatePassword('confirm', $event)"
        />

        <hr />

        <h3>Zwei-Faktor-Authentifizierung</h3>
        <p v-if="factorEnabled">
            Die Zwei-Faktor-Authentifizierung ist aktiviert. Zum Deaktivieren benötigen Sie Ihr
            aktuelles Passwort.
        </p>
        <p v-else-if="factorPending">
            Die Einrichtung wurde begonnen. Bestätigen Sie sie mit einem Code aus Ihrer
            Authenticator-App.
        </p>
        <p v-else>Schützen Sie Ihr Konto zusätzlich mit einer Authenticator-App.</p>

        <FormInput
            v-if="factorEnabled"
            name="two_factor_current_password"
            label="Aktuelles Passwort zum Deaktivieren"
            type="password"
            autocomplete="current-password"
            required
            v-model="twoFactor.currentPassword"
        />
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
            @click="disableTwoFactor"
        >
            Zwei-Faktor-Authentifizierung deaktivieren
        </button>

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
                <h4 id="two-factor-setup-title">Authenticator-App einrichten</h4>
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
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-testid="two-factor-intro-next"
                        @click="setupStep = 2"
                    >
                        Weiter
                    </button>
                </div>
                <form
                    v-else-if="setupStep === 2"
                    @submit.prevent="startTwoFactor"
                >
                    <p>Bestätigen Sie Ihre Identität mit Ihrem aktuellen Passwort.</p>
                    <FormInput
                        name="two_factor_current_password"
                        label="Aktuelles Passwort"
                        type="password"
                        autocomplete="current-password"
                        required
                        v-model="twoFactor.currentPassword"
                    />
                    <button
                        type="submit"
                        class="btn btn-primary"
                        data-testid="two-factor-start"
                        :disabled="twoFactor.busy || !twoFactor.currentPassword"
                    >
                        Einrichtung starten
                    </button>
                </form>
                <div
                    v-else-if="setupStep === 3 && setup"
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
                    <details>
                        <summary>QR-Code lässt sich nicht scannen?</summary>
                        <p>Geben Sie diesen Schlüssel manuell in Ihrer App ein:</p>
                        <code class="d-block text-break">{{ provisioningSecret }}</code>
                    </details>
                    <h5>Recovery-Codes</h5>
                    <p>
                        Bewahren Sie diese Codes sicher auf. Jeder Code kann nur einmal verwendet
                        werden.
                    </p>
                    <ul>
                        <li
                            v-for="code in setup.recovery_codes"
                            :key="code"
                        >
                            <code>{{ code }}</code>
                        </li>
                    </ul>
                    <label
                        ><input
                            v-model="recoveryCodesSaved"
                            type="checkbox"
                            data-testid="recovery-codes-saved"
                        />
                        Ich habe die Recovery-Codes sicher gespeichert.</label
                    >
                    <button
                        type="button"
                        class="btn btn-primary mt-3"
                        data-testid="two-factor-setup-next"
                        :disabled="!recoveryCodesSaved"
                        @click="setupStep = 4"
                    >
                        Weiter
                    </button>
                </div>
                <form
                    v-else-if="setupStep === 4"
                    @submit.prevent="confirmTwoFactor"
                >
                    <p>
                        Geben Sie einen aktuellen sechsstelligen Code aus Ihrer Authenticator-App
                        ein, um die Einrichtung abzuschließen.
                    </p>
                    <FormInput
                        name="two_factor_current_password"
                        label="Aktuelles Passwort zur Bestätigung"
                        type="password"
                        autocomplete="current-password"
                        required
                        v-model="twoFactor.currentPassword"
                    />
                    <OneTimeCodeInput
                        v-model="twoFactor.code"
                        label="Sechsstelliger Code"
                        name="two_factor_code"
                        test-id-prefix="totp"
                    />
                    <button
                        type="submit"
                        class="btn btn-primary mt-3"
                        data-testid="two-factor-confirm"
                        :disabled="
                            twoFactor.busy ||
                            !twoFactor.currentPassword ||
                            twoFactor.code.length !== 6
                        "
                    >
                        Einrichtung bestätigen
                    </button>
                </form>
                <button
                    type="button"
                    class="btn btn-link"
                    :disabled="twoFactor.busy"
                    @click="closeSetup"
                >
                    Schließen
                </button>
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
import FormInput from '../../Ui/forms/FormInput'
import OneTimeCodeInput from '../../Ui/forms/OneTimeCodeInput.vue'
export default {
    name: 'SecurityTab',
    components: { FormInput, OneTimeCodeInput },
    props: {
        user: { type: Object, required: true },
        password: { type: Object, required: true },
        twoFactorEnabled: { type: Boolean, default: false },
        twoFactorPending: { type: Boolean, default: false },
    },
    emits: ['two-factor-enabled', 'two-factor-disabled', 'update:password'],
    data() {
        return {
            setup: null,
            qrCode: '',
            setupStep: 1,
            setupModalOpen: false,
            recoveryCodesSaved: false,
            factorEnabled: this.twoFactorEnabled,
            factorPending: this.twoFactorPending,
            twoFactor: {
                busy: false,
                code: '',
                currentPassword: '',
                error: '',
            },
        }
    },
    computed: {
        /** Read the TOTP key locally from the provisioning URI. */
        provisioningSecret() {
            return new window.URL(this.setup.provisioning_uri).searchParams.get('secret') || ''
        },
    },
    methods: {
        /** Emit a password change without mutating the parent-owned object. */
        updatePassword(field, value) {
            this.$emit('update:password', { ...this.password, [field]: value })
        },
        /** Open and reset the guided enrollment dialog. */
        openSetup() {
            this.setup = null
            this.qrCode = ''
            this.setupStep = 1
            this.recoveryCodesSaved = false
            this.twoFactor.code = ''
            this.setupModalOpen = true
            this.$nextTick(() => this.$el.querySelector('.setup-dialog')?.focus())
        },
        /** Clear transient secrets when the enrollment dialog is closed. */
        closeSetup() {
            this.setupModalOpen = false
            this.setup = null
            this.qrCode = ''
            this.recoveryCodesSaved = false
            this.twoFactor.code = ''
            this.twoFactor.currentPassword = ''
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
        /** Start a re-authenticated TOTP enrolment. */
        async startTwoFactor() {
            await this.runTwoFactorRequest(
                () =>
                    axios.post(window.route('user.two-factor.start'), {
                        current_password: this.twoFactor.currentPassword,
                    }),
                async (response) => {
                    this.setup = response.data
                    this.qrCode = await QRCode.toDataURL(response.data.provisioning_uri)
                    this.setupStep = 3
                },
            )
        },
        /** Confirm the code from the authenticator application. */
        async confirmTwoFactor() {
            await this.runTwoFactorRequest(
                () =>
                    axios.post(window.route('user.two-factor.confirm'), {
                        current_password: this.twoFactor.currentPassword,
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
        /** Disable TOTP after fresh password re-authentication. */
        async disableTwoFactor() {
            if (!window.confirm('Zwei-Faktor-Authentifizierung wirklich deaktivieren?')) return

            await this.runTwoFactorRequest(
                () =>
                    axios.post(window.route('user.two-factor.disable'), {
                        current_password: this.twoFactor.currentPassword,
                    }),
                () => {
                    this.factorEnabled = false
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
                        : 'Die Zwei-Faktor-Aktion konnte nicht ausgeführt werden.'
            } finally {
                this.twoFactor.currentPassword = ''
                this.twoFactor.busy = false
            }
        },
    },
}
</script>

<style scoped>
.password-requirements {
    max-width: 40rem;
}
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

.setup-dialog img {
    display: block;
    width: min(100%, 18rem);
    margin: 1rem auto;
}
</style>

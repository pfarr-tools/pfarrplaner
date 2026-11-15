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
            name="two_factor_current_password"
            label="Aktuelles Passwort für die Zwei-Faktor-Aktion"
            type="password"
            autocomplete="current-password"
            v-model="twoFactor.currentPassword"
        />
        <button
            v-if="!factorEnabled && !setup"
            type="button"
            class="btn btn-secondary"
            :disabled="twoFactor.busy"
            @click="startTwoFactor"
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

        <div
            v-if="setup"
            class="two-factor-setup"
            data-dusk="two-factor-setup"
        >
            <p>
                Fügen Sie in Ihrer Authenticator-App manuell ein zeitbasiertes Einmalpasswort (TOTP)
                hinzu und geben Sie diesen Schlüssel ein. Bewahren Sie die Recovery-Codes sicher
                auf.
            </p>
            <p>
                Konto: <strong>{{ user.email }}</strong>
            </p>
            <p>
                TOTP-Schlüssel:
                <code class="d-block text-break">{{ provisioningSecret }}</code>
            </p>
            <h4>Recovery-Codes</h4>
            <ul>
                <li
                    v-for="code in setup.recovery_codes"
                    :key="code"
                >
                    <code>{{ code }}</code>
                </li>
            </ul>
            <FormInput
                name="two_factor_code"
                label="Code aus der Authenticator-App"
                autocomplete="one-time-code"
                v-model="twoFactor.code"
            />
            <button
                type="button"
                class="btn btn-primary"
                :disabled="twoFactor.busy"
                @click="confirmTwoFactor"
            >
                Einrichtung bestätigen
            </button>
        </div>
        <div
            v-if="twoFactor.error"
            class="alert alert-danger mt-3"
            role="alert"
        >
            {{ twoFactor.error }}
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import FormInput from '../../Ui/forms/FormInput'
export default {
    name: 'SecurityTab',
    components: { FormInput },
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
        /** Start a re-authenticated TOTP enrolment. */
        async startTwoFactor() {
            await this.runTwoFactorRequest(
                () =>
                    axios.post(window.route('user.two-factor.start'), {
                        current_password: this.twoFactor.currentPassword,
                    }),
                (response) => {
                    this.setup = response.data
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
                    this.twoFactor.code = ''
                    this.$emit('two-factor-enabled')
                    this.twoFactor.error = ''
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
                onSuccess(response)
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
</style>

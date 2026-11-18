<template>
    <main class="container py-4 two-factor-recovery">
        <h1>Zwei-Faktor-Authentifizierung erneut einrichten</h1>
        <p>
            Ein Administrator hat den verlorenen Faktor widerrufen. Richte TOTP mit einer
            Authenticator-App neu ein. Erst nach erfolgreicher Bestätigung ist dein Konto wieder
            vollständig nutzbar.
        </p>

        <button
            v-if="!setup && !complete"
            type="button"
            class="btn btn-primary"
            data-testid="start-enrolment"
            :disabled="busy"
            @click="startEnrolment"
        >
            Authenticator einrichten
        </button>

        <section
            v-if="setup"
            class="two-factor-recovery-setup mt-4"
            data-dusk="two-factor-recovery-setup"
            data-testid="two-factor-recovery-setup"
        >
            <h2>Authenticator-App verbinden</h2>
            <p>
                Füge diesen Schlüssel als zeitbasiertes Einmalpasswort (TOTP) in deiner
                Authenticator-App hinzu. Bewahre die Recovery-Codes sicher auf; sie werden nur
                während dieser Einrichtung angezeigt.
            </p>
            <p>
                TOTP-Schlüssel:
                <code class="d-block text-break">{{ provisioningSecret }}</code>
            </p>
            <h3>Recovery-Codes</h3>
            <ul>
                <li
                    v-for="recoveryCode in setup.recovery_codes"
                    :key="recoveryCode"
                >
                    <code>{{ recoveryCode }}</code>
                </li>
            </ul>
            <FormInput
                name="totp_code"
                label="Code aus der Authenticator-App"
                autocomplete="one-time-code"
                v-model="code"
            />
            <button
                type="button"
                class="btn btn-primary"
                data-testid="confirm-enrolment"
                :disabled="busy"
                @click="confirmEnrolment"
            >
                TOTP bestätigen
            </button>
        </section>

        <p
            v-if="complete"
            class="alert alert-success"
            role="status"
        >
            TOTP wurde eingerichtet. Dein Konto ist wieder freigegeben.
        </p>
        <p
            v-if="error"
            class="alert alert-danger mt-3"
            role="alert"
        >
            {{ error }}
        </p>
        <a
            class="d-block mt-4"
            href="/logout"
            >Abmelden</a
        >
    </main>
</template>

<script>
import axios from 'axios'
import FormInput from '@/components/Ui/forms/FormInput.vue'

export default {
    name: 'TwoFactorRecovery',
    components: { FormInput },
    emits: ['enrolment-complete'],
    /** Return transient TOTP setup state; generated setup data stays in memory only. */
    data() {
        return {
            busy: false,
            code: '',
            complete: false,
            error: '',
            setup: null,
        }
    },
    computed: {
        /** Extract the one-time TOTP key locally without sending it to another service. */
        provisioningSecret() {
            if (!this.setup?.provisioning_uri) return ''

            return new window.URL(this.setup.provisioning_uri).searchParams.get('secret') || ''
        },
    },
    methods: {
        /** Start TOTP setup for the authenticated account. */
        async startEnrolment() {
            await this.runRequest(
                () => axios.post(window.route('user.two-factor.start'), {}),
                (response) => {
                    this.setup = response.data
                },
            )
        },
        /** Confirm the first TOTP code and immediately clear displayed secret material. */
        async confirmEnrolment() {
            await this.runRequest(
                () =>
                    axios.post(window.route('user.two-factor.confirm'), {
                        code: this.code,
                    }),
                () => {
                    this.setup = null
                    this.code = ''
                    this.complete = true
                    this.error = ''
                    this.$emit('enrolment-complete')
                },
            )
        },
        /** Run a TOTP request and keep server details and submitted secrets out of UI state. */
        async runRequest(request, onSuccess) {
            this.busy = true
            this.error = ''

            try {
                const response = await request()
                onSuccess(response)
            } catch (error) {
                this.error =
                    error.response?.status === 429
                        ? 'Zu viele Versuche. Bitte später erneut versuchen.'
                        : 'Die Zwei-Faktor-Einrichtung konnte nicht ausgeführt werden.'
            } finally {
                this.busy = false
            }
        },
    },
}
</script>

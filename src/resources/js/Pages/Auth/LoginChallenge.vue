<template>
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <h1 class="mb-4">Anmeldung bestätigen</h1>
                <p>Gib den Sicherheitscode ein, um die Anmeldung abzuschließen.</p>
                <p class="text-muted">
                    Die Anmeldung läuft nach fünf Minuten ab. Schließe dieses Fenster und beginne
                    erneut, falls die Challenge abgelaufen ist.
                </p>
                <form @submit.prevent="verify">
                    <OneTimeCodeInput
                        v-if="codeMode !== 'recovery'"
                        v-model="code"
                        :length="6"
                        label="Sechsstelliger App-Code"
                        test-id-prefix="login-totp"
                    />
                    <label
                        v-else
                        class="form-label"
                        for="recovery-code"
                        >Recovery-Code</label
                    >
                    <input
                        v-if="codeMode === 'recovery'"
                        id="recovery-code"
                        v-model="code"
                        class="form-control"
                        name="code"
                        type="text"
                        autocomplete="one-time-code"
                        maxlength="128"
                        required
                        autofocus
                    />
                    <div
                        v-if="$page.props.errors?.code"
                        class="text-danger mt-2"
                    >
                        {{ $page.props.errors.code }}
                    </div>
                    <button
                        class="btn btn-primary mt-3"
                        type="submit"
                        :disabled="processing"
                    >
                        Anmeldung bestätigen
                    </button>
                </form>
                <div
                    class="mt-2 d-flex flex-wrap gap-2"
                    aria-label="Code-Art auswählen"
                >
                    <button
                        type="button"
                        class="btn btn-link px-0"
                        @click="switchCodeMode('totp')"
                    >
                        Authenticator-App verwenden
                    </button>
                    <button
                        type="button"
                        class="btn btn-link px-0"
                        @click="switchCodeMode('recovery')"
                    >
                        Recovery-Code verwenden
                    </button>
                </div>
                <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                    <button
                        class="btn btn-link px-0"
                        type="button"
                        @click="cancel"
                    >
                        Anmeldung abbrechen
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import OneTimeCodeInput from '../../components/Ui/forms/OneTimeCodeInput.vue'

export default {
    components: { OneTimeCodeInput },
    /** Initialize the one-time code form and request state. */
    data() {
        return {
            code: '',
            codeMode: 'totp',
            processing: false,
        }
    },
    watch: {
        /** Automatically submit a complete authenticator code. */
        code(value) {
            if (this.codeMode === 'totp' && /^\d{6}$/.test(value) && !this.processing) {
                this.verify()
            }
        },
    },
    /** Focus the initial challenge input after Vue has rendered it. */
    mounted() {
        this.$nextTick(() => this.focusCurrentInput())
    },
    methods: {
        /** Submit an authenticator or recovery code. */
        verify() {
            if (this.processing) return
            this.processing = true
            this.$inertia.post(
                window.route('login.challenge.verify'),
                { code: this.code },
                {
                    preserveState: true,
                    onFinish: () => {
                        this.processing = false
                    },
                },
            )
        },
        /** Clear any previous code when the user changes verification method. */
        switchCodeMode(mode) {
            this.codeMode = mode
            this.code = ''
            this.$nextTick(() => this.focusCurrentInput())
        },
        /** Focus the first input for the selected challenge method. */
        focusCurrentInput() {
            const selector =
                this.codeMode === 'recovery' ? '#recovery-code' : '[data-code-index="0"]'
            this.$el.querySelector(selector)?.focus()
        },
        /** Clear the pending login and return to the sign-in methods. */
        cancel() {
            this.$inertia.post(window.route('login.challenge.cancel'))
        },
    },
}
</script>

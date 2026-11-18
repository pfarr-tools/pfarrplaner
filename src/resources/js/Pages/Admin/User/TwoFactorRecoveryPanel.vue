<template>
    <section
        v-if="canRecover || history.length || unavailableReason"
        class="mt-4 border-top pt-3"
        aria-labelledby="two-factor-recovery-title"
    >
        <h3 id="two-factor-recovery-title">Zwei-Faktor-Wiederherstellung</h3>
        <p>
            Vor dem Widerruf muss die Identität der Person geprüft werden. Das Konto wird danach bis
            zur erneuten TOTP-Einrichtung gesperrt; vorhandene Sitzungen und API-Tokens werden
            widerrufen.
        </p>
        <p
            v-if="unavailableReason"
            class="alert alert-info"
            role="status"
        >
            {{ unavailableReason }}
        </p>
        <button
            v-if="recoveryAvailable && !formOpen"
            type="button"
            class="btn btn-outline-danger"
            data-testid="open-two-factor-recovery"
            @click="openForm"
        >
            Verlorene Zwei-Faktor-Anmeldung widerrufen
        </button>

        <form
            v-if="recoveryAvailable && formOpen"
            class="mt-3"
            @submit.prevent="recover"
        >
            <div class="alert alert-warning">
                Prüfe die Identität und dokumentiere den Supportfall, bevor du fortfährst.
            </div>
            <label
                class="form-label"
                for="two-factor-recovery-code"
                >Dein aktueller Sicherheitscode</label
            >
            <input
                id="two-factor-recovery-code"
                v-model="code"
                class="form-control mb-3"
                name="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
            />
            <label
                class="form-label"
                for="two-factor-support-reference"
                >Supportfall-Referenz</label
            >
            <input
                id="two-factor-support-reference"
                v-model="supportReference"
                class="form-control mb-3"
                name="support_reference"
                autocomplete="off"
                maxlength="100"
                required
            />
            <div class="d-flex gap-2">
                <button
                    type="submit"
                    class="btn btn-danger"
                    :disabled="submitting || !code || !supportReference"
                    data-testid="submit-two-factor-recovery"
                >
                    Wiederherstellung auslösen
                </button>
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-testid="cancel-two-factor-recovery"
                    @click="closeForm"
                >
                    Abbrechen
                </button>
            </div>
            <p
                v-if="error"
                class="alert alert-danger mt-3"
                role="alert"
            >
                {{ error }}
            </p>
        </form>

        <div
            v-if="history.length"
            class="mt-4"
        >
            <h4>Wiederherstellungsverlauf</h4>
            <ul>
                <li
                    v-for="(entry, index) in history"
                    :key="`${entry.createdAt}-${index}`"
                >
                    {{ entry.createdAt }} — {{ entry.actor.name }} (ID {{ entry.actor.id }}),
                    Supportfall {{ entry.supportReference }}
                </li>
            </ul>
        </div>
    </section>
</template>

<script>
export default {
    name: 'TwoFactorRecoveryPanel',
    props: {
        canRecover: { type: Boolean, default: false },
        unavailableReason: { type: String, default: '' },
        history: {
            type: Array,
            default: function defaultHistory() {
                return []
            },
        },
        recoveryRoute: { type: String, required: true },
    },
    /** Derivates whether every server-provided recovery prerequisite is met. */
    computed: {
        recoveryAvailable() {
            return this.canRecover && !this.unavailableReason
        },
    },
    /** Clear code and reference immediately when recovery becomes unavailable. */
    watch: {
        recoveryAvailable(available) {
            if (!available) {
                this.closeForm()
            }
        },
    },
    /** Return local form state without storing credentials in page props. */
    data() {
        return {
            code: '',
            supportReference: '',
            formOpen: false,
            submitting: false,
            error: '',
        }
    },
    methods: {
        /** Open a fresh form and clear any previous authentication input. */
        openForm() {
            this.code = ''
            this.supportReference = ''
            this.error = ''
            this.formOpen = true
        },
        /** Close the form and discard the code and support reference. */
        closeForm() {
            this.code = ''
            this.supportReference = ''
            this.error = ''
            this.formOpen = false
        },
        /** Submit only the required TOTP confirmation and audit reference fields. */
        recover() {
            if (!this.recoveryAvailable) {
                return
            }

            this.submitting = true
            this.error = ''
            const panel = this
            this.$inertia.post(
                this.recoveryRoute,
                {
                    code: this.code,
                    support_reference: this.supportReference,
                },
                {
                    onSuccess: function handleSuccess() {
                        panel.code = ''
                        panel.supportReference = ''
                        panel.formOpen = false
                    },
                    onError: function handleError() {
                        panel.error = 'Die Wiederherstellung konnte nicht durchgeführt werden.'
                    },
                    onFinish: function handleFinish() {
                        panel.code = ''
                        panel.submitting = false
                    },
                },
            )
        },
    },
}
</script>

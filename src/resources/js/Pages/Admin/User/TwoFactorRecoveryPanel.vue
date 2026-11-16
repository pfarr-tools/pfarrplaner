<template>
    <section
        v-if="canRecover"
        class="mt-4 border-top pt-3"
        aria-labelledby="two-factor-recovery-title"
    >
        <h3 id="two-factor-recovery-title">Zwei-Faktor-Wiederherstellung</h3>
        <p>
            Vor dem Widerruf muss die Identität der Person geprüft werden. Das Konto wird danach bis
            zur erneuten TOTP-Einrichtung gesperrt; vorhandene Sitzungen und API-Tokens werden
            widerrufen.
        </p>
        <button
            v-if="!formOpen"
            type="button"
            class="btn btn-outline-danger"
            data-testid="open-two-factor-recovery"
            @click="openForm"
        >
            Verlorene Zwei-Faktor-Anmeldung widerrufen
        </button>

        <form
            v-if="formOpen"
            class="mt-3"
            @submit.prevent="recover"
        >
            <div class="alert alert-warning">
                Prüfe die Identität und dokumentiere den Supportfall, bevor du fortfährst.
            </div>
            <label
                class="form-label"
                for="two-factor-current-password"
                >Aktuelles Passwort</label
            >
            <input
                id="two-factor-current-password"
                v-model="currentPassword"
                class="form-control mb-3"
                name="current_password"
                type="password"
                autocomplete="current-password"
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
                    :disabled="submitting || !currentPassword || !supportReference"
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
        history: {
            type: Array,
            default: function defaultHistory() {
                return []
            },
        },
        recoveryRoute: { type: String, required: true },
    },
    /** Return local form state without storing credentials in page props. */
    data() {
        return {
            currentPassword: '',
            supportReference: '',
            formOpen: false,
            submitting: false,
            error: '',
        }
    },
    methods: {
        /** Open a fresh form and clear any previous authentication input. */
        openForm() {
            this.currentPassword = ''
            this.supportReference = ''
            this.error = ''
            this.formOpen = true
        },
        /** Close the form and discard the password and support reference. */
        closeForm() {
            this.currentPassword = ''
            this.supportReference = ''
            this.error = ''
            this.formOpen = false
        },
        /** Submit only the required reauthentication and audit reference fields. */
        recover() {
            this.submitting = true
            this.error = ''
            const panel = this
            this.$inertia.post(
                this.recoveryRoute,
                {
                    current_password: this.currentPassword,
                    support_reference: this.supportReference,
                },
                {
                    onSuccess: function handleSuccess() {
                        panel.currentPassword = ''
                        panel.supportReference = ''
                        panel.formOpen = false
                    },
                    onError: function handleError() {
                        panel.error = 'Die Wiederherstellung konnte nicht durchgeführt werden.'
                    },
                    onFinish: function handleFinish() {
                        panel.currentPassword = ''
                        panel.submitting = false
                    },
                },
            )
        },
    },
}
</script>

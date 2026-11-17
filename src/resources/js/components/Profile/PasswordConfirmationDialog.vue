<template>
    <section
        v-if="show"
        class="password-confirmation-backdrop"
        data-testid="password-confirmation-dialog"
        @click.self="cancel"
    >
        <div
            class="password-confirmation-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="password-confirmation-title"
            tabindex="-1"
            @keydown.esc.stop.prevent="cancel"
            @keydown.tab="trapFocus"
        >
            <div class="d-flex justify-content-between align-items-start gap-3">
                <h4 id="password-confirmation-title">{{ title }}</h4>
                <button
                    type="button"
                    class="btn-close"
                    aria-label="Fenster schließen"
                    data-testid="password-confirmation-close"
                    :disabled="busy"
                    @click="cancel"
                />
            </div>
            <p>{{ description }}</p>
            <form @submit.prevent="submit">
                <label
                    class="form-label"
                    for="password-confirmation-dialog-input"
                >
                    Aktuelles Passwort
                </label>
                <input
                    id="password-confirmation-dialog-input"
                    ref="passwordInput"
                    v-model="password"
                    class="form-control"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    data-testid="password-confirmation-input"
                />
                <p
                    v-if="error"
                    class="alert alert-danger mt-3"
                    role="alert"
                >
                    {{ error }}
                </p>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-testid="password-confirmation-cancel"
                        :disabled="busy"
                        @click="cancel"
                    >
                        Abbrechen
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        data-testid="password-confirmation-submit"
                        :disabled="busy || !password"
                    >
                        {{ busy ? 'Wird geprüft …' : 'Bestätigen' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<script>
/**
 * Modal password prompt for sensitive profile actions.
 * @prop {boolean} show Whether the dialog is visible.
 * @prop {string} title Dialog title.
 * @prop {string} description Explanation of the pending action.
 * @prop {boolean} busy Whether confirmation is being processed.
 * @prop {string} error Safe error text to display.
 * @emits confirm Current password, emitted once and removed from local state.
 * @emits cancel Dialog cancellation.
 */
export default {
    name: 'PasswordConfirmationDialog',
    props: {
        show: { type: Boolean, default: false },
        title: { type: String, required: true },
        description: { type: String, required: true },
        busy: { type: Boolean, default: false },
        error: { type: String, default: '' },
    },
    emits: ['confirm', 'cancel'],
    data() {
        return { password: '' }
    },
    watch: {
        /** Focus the password field when the dialog opens and clear it on close. */
        show(visible) {
            if (!visible) {
                this.password = ''
                return
            }

            this.$nextTick(() => this.$refs.passwordInput?.focus())
        },
    },
    methods: {
        /** Submit the password once and remove it from local component state. */
        submit() {
            if (this.busy || !this.password) return

            const password = this.password
            this.password = ''
            this.$emit('confirm', password)
        },
        /** Close the dialog and clear any unsubmitted password. */
        cancel() {
            if (this.busy) return

            this.password = ''
            this.$emit('cancel')
        },
        /** Keep keyboard focus within the modal dialog. */
        trapFocus(event) {
            const dialog = event.currentTarget
            const focusable = Array.from(
                dialog.querySelectorAll('button:not([disabled]), input:not([disabled])'),
            )
            if (!focusable.length) {
                event.preventDefault()
                return
            }

            const first = focusable[0]
            const last = focusable[focusable.length - 1]
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        },
    },
}
</script>

<style scoped>
.password-confirmation-backdrop {
    position: fixed;
    z-index: 1060;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / 55%);
}

.password-confirmation-dialog {
    width: min(100%, 32rem);
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.5rem;
    border-radius: 0.5rem;
    background: var(--bs-body-bg, #fff);
}
</style>

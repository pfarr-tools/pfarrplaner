<template>
    <Head title="Kontosicherheit" />
    <main
        class="container py-5"
        data-testid="security-onboarding"
    >
        <div
            class="mx-auto"
            style="max-width: 52rem"
        >
            <h1>Schütze dein Konto zusätzlich</h1>
            <p class="lead">
                Willkommen, {{ user.firstName }}. Dein Konto ist derzeit nur durch den Link in
                deiner E-Mail geschützt. Mit einem Passkey oder einer Authenticator-App wird es
                deutlich schwerer, dass jemand anderes Zugriff erhält.
            </p>

            <SecurityTab
                :user="user"
                :two-factor-enabled="twoFactorEnabled"
                :two-factor-pending="twoFactorPending"
                :passkeys="passkeys"
                @two-factor-enabled="showContinueButton"
                @passkey-created="showContinueButton"
            />

            <hr class="my-4" />
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a
                    v-if="continueAvailable"
                    class="btn btn-primary"
                    :href="route('home')"
                    data-testid="continue-after-setup"
                >
                    Weiter zur Anwendung
                </a>
                <form
                    method="post"
                    :action="route('security.onboarding.later')"
                >
                    <FormCsrfToken />
                    <button
                        type="submit"
                        class="btn btn-outline-secondary"
                        data-testid="defer-onboarding"
                    >
                        Später erinnern
                    </button>
                </form>
            </div>
            <p class="form-text mt-2">
                Wenn du die Einrichtung jetzt überspringen möchtest, kannst du Pfarrplaner mit
                „Später erinnern“ bis zum Ende dieser Sitzung nutzen. Der Hinweis erscheint bei
                einer späteren Anmeldung erneut, solange kein Passkey oder keine Authenticator-App
                eingerichtet ist.
            </p>
        </div>
    </main>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import SecurityTab from '@/components/Profile/Tabs/SecurityTab.vue'
import FormCsrfToken from '@/components/Ui/forms/FormCsrfToken.vue'

/** Return an empty passkey list when Inertia does not provide one. */
function emptyPasskeys() {
    return []
}

/** Explain and start the account's first additional security factor. */
export default {
    name: 'SecurityOnboarding',
    components: { Head, SecurityTab, FormCsrfToken },
    props: {
        user: { type: Object, required: true },
        twoFactorEnabled: { type: Boolean, default: false },
        twoFactorPending: { type: Boolean, default: false },
        passkeys: { type: Array, default: emptyPasskeys },
    },
    /** Track whether this session may navigate past the security reminder. */
    data() {
        return {
            continueAvailable: this.twoFactorEnabled || this.passkeys.length > 0,
        }
    },
    /** Reveal normal application navigation after either factor is enrolled. */
    methods: {
        /** Reveal application access after a passkey or TOTP factor is set up. */
        showContinueButton() {
            this.continueAvailable = true
        },
    },
}
</script>

<template>
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

            <section class="card mb-4">
                <div class="card-body">
                    <h2 class="h4">Passkey: die einfachste Anmeldung</h2>
                    <p>
                        Ein Passkey nutzt die Displaysperre deines Geräts, zum Beispiel
                        Fingerabdruck, Gesichtserkennung oder Geräte-PIN. Er ist an dein Gerät
                        gebunden und kann nicht wie ein E-Mail-Link weitergegeben oder abgefangen
                        werden.
                    </p>
                    <p class="mb-0">
                        Richte nach Möglichkeit einen Passkey auf jedem Gerät ein, mit dem du dich
                        anmeldest. So kannst du dich später direkt und ohne E-Mail-Link anmelden.
                    </p>
                </div>
            </section>

            <section class="card mb-4">
                <div class="card-body">
                    <h2 class="h4">Authenticator-App: Einmalcodes als zusätzlicher Schutz</h2>
                    <p>
                        Eine Authenticator-App erzeugt alle paar Sekunden einen neuen sechsstelligen
                        Code. Nach dem E-Mail-Link gibst du diesen Code ein. Geeignete Apps sind
                        <a
                            href="https://support.microsoft.com/de-de/authenticator/download-microsoft-authenticator"
                            target="_blank"
                            rel="noopener noreferrer"
                            >Microsoft Authenticator</a
                        >
                        und
                        <a
                            href="https://2fas.com/auth/"
                            target="_blank"
                            rel="noopener noreferrer"
                            >2FAS Auth</a
                        >, eine quelloffene App.
                    </p>
                    <p class="mb-0">
                        Du kannst einen Passkey oder eine Authenticator-App einrichten; beides
                        zusammen bietet zusätzliche Ausweichmöglichkeiten.
                    </p>
                </div>
            </section>

            <SecurityTab
                :user="user"
                :two-factor-enabled="twoFactorEnabled"
                :two-factor-pending="twoFactorPending"
                :passkeys="passkeys"
            />

            <div class="d-flex flex-wrap gap-2 mt-4">
                <a
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
                Wenn du „Später erinnern“ wählst, kannst du Pfarrplaner in dieser Sitzung nutzen.
                Der Hinweis erscheint bei einer späteren Anmeldung erneut, solange kein Passkey oder
                keine Authenticator-App eingerichtet ist.
            </p>
        </div>
    </main>
</template>

<script>
import SecurityTab from '@/components/Profile/Tabs/SecurityTab.vue'
import FormCsrfToken from '@/components/Ui/forms/FormCsrfToken.vue'

/** Return an empty passkey list when Inertia does not provide one. */
function emptyPasskeys() {
    return []
}

/** Explain and start the account's first additional security factor. */
export default {
    name: 'SecurityOnboarding',
    components: { SecurityTab, FormCsrfToken },
    props: {
        user: { type: Object, required: true },
        twoFactorEnabled: { type: Boolean, default: false },
        twoFactorPending: { type: Boolean, default: false },
        passkeys: { type: Array, default: emptyPasskeys },
    },
}
</script>

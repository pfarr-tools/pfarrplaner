<template>
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <h1 class="mb-4">Anmeldung bestätigen</h1>
                <p>
                    Für dieses Konto ist eine Zwei-Faktor-Anmeldung aktiviert. Gib den Code aus
                    deiner Authenticator-App ein oder fordere einen Code per E-Mail an.
                </p>
                <p class="text-muted">
                    Die Anmeldung läuft nach fünf Minuten ab. Schließe dieses Fenster und beginne
                    erneut, falls die Challenge abgelaufen ist.
                </p>
                <form @submit.prevent="verify">
                    <label
                        for="code"
                        class="form-label"
                        >Authenticator- oder E-Mail-Code</label
                    >
                    <input
                        id="code"
                        v-model="code"
                        class="form-control"
                        name="code"
                        autocomplete="one-time-code"
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
                <form
                    class="mt-3"
                    @submit.prevent="requestEmailCode"
                >
                    <button
                        class="btn btn-outline-primary"
                        type="submit"
                        :disabled="processing"
                    >
                        Code per E-Mail anfordern
                    </button>
                </form>
                <p
                    v-if="$page.props.flash?.success"
                    class="text-success mt-2"
                    role="status"
                >
                    {{ $page.props.flash.success }}
                </p>
                <form
                    class="mt-3"
                    @submit.prevent="cancel"
                >
                    <button
                        class="btn btn-link px-0"
                        type="submit"
                    >
                        Anmeldung abbrechen
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    /** Initialize the one-time code form and request state. */
    data() {
        return {
            code: '',
            processing: false,
        }
    },
    methods: {
        /** Submit an authenticator, recovery, or email code. */
        verify() {
            this.processing = true
            this.$inertia.post(
                route('login.challenge.verify'),
                { code: this.code },
                {
                    preserveState: true,
                    onFinish: () => {
                        this.processing = false
                    },
                },
            )
        },
        /** Request an email fallback code for the pending login. */
        requestEmailCode() {
            this.processing = true
            this.$inertia.post(
                route('login.challenge.email'),
                {},
                {
                    preserveState: true,
                    onFinish: () => {
                        this.processing = false
                    },
                },
            )
        },
        /** Clear the pending login and return to the password form. */
        cancel() {
            this.$inertia.post(route('login.challenge.cancel'))
        },
    },
}
</script>

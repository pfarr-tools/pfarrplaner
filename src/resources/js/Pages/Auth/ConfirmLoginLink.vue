<template>
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <h1 class="mb-4">Anmeldung bestätigen</h1>
                <p>
                    Wenn du diesen Anmeldelink selbst angefordert hast, bestätige jetzt die
                    Anmeldung.
                </p>
                <p class="text-muted">
                    Der Link läuft zehn Minuten nach der Anforderung ab und kann nur einmal
                    verwendet werden.
                </p>
                <p
                    v-if="$page.props.errors?.link"
                    class="text-danger"
                    role="alert"
                >
                    {{ $page.props.errors.link }}
                </p>
                <form @submit.prevent="confirm">
                    <button
                        class="btn btn-primary"
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Anmeldung wird geprüft …' : 'Jetzt anmelden' }}
                    </button>
                    <a
                        class="btn btn-link ms-2"
                        :href="route('login')"
                        >Abbrechen</a
                    >
                </form>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: 'ConfirmLoginLink',
    /** Track the confirmation request while preventing accidental duplicate submits. */
    data() {
        return { processing: false }
    },
    methods: {
        /** Confirm the link through a same-origin POST after the token URL was opened. */
        confirm() {
            if (this.processing) return
            this.processing = true
            this.$inertia.post(
                window.route('login.link.confirm'),
                {},
                {
                    onFinish: () => {
                        this.processing = false
                    },
                },
            )
        },
    },
}
</script>

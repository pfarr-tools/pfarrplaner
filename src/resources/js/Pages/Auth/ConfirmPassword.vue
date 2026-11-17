<template>
    <section
        class="container py-5"
        style="max-width: 32rem"
    >
        <h1 class="mb-3">Passwort bestätigen</h1>
        <p>Bestätige dein aktuelles Passwort, um sensible Sicherheitseinstellungen zu ändern.</p>
        <form @submit.prevent="submit">
            <FormCsrfToken />
            <input
                type="hidden"
                name="redirect"
                :value="redirect"
            />
            <FormInput
                v-model="password"
                name="password"
                label="Aktuelles Passwort"
                type="password"
                autocomplete="current-password"
                required
                autofocus
            />
            <p
                v-if="errors.password"
                class="text-danger"
                role="alert"
            >
                {{ errors.password }}
            </p>
            <button
                class="btn btn-primary"
                type="submit"
                :disabled="processing"
            >
                Bestätigen
            </button>
            <a
                class="btn btn-link"
                :href="redirect || '/'"
                >Abbrechen</a
            >
        </form>
    </section>
</template>

<script>
import FormInput from '../../components/Ui/forms/FormInput.vue'
import FormCsrfToken from '../../components/Ui/forms/FormCsrfToken.vue'

export default {
    name: 'ConfirmPassword',
    components: { FormInput, FormCsrfToken },
    props: {
        redirect: { type: String, default: '' },
        errors: { type: Object, default: () => ({}) },
    },
    data() {
        return { password: '', processing: false }
    },
    methods: {
        /** Submit the current password without retaining it after navigation. */
        submit() {
            if (this.processing) return
            this.processing = true
            this.$inertia.post(
                window.route('password.confirm.store'),
                { password: this.password, redirect: this.redirect },
                {
                    onFinish: () => {
                        this.password = ''
                        this.processing = false
                    },
                },
            )
        },
    },
}
</script>

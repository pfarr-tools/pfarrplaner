<template>
    <div class="mt-3 rounded border p-3">
        <h4>Manuellen Anmeldelink</h4>
        <p>
            Erstellt einen einmalig verwendbaren Link. Er gilt 60 Minuten; eine eingerichtete
            Zwei-Faktor-Anmeldung bleibt erforderlich.
        </p>
        <button
            type="button"
            class="btn btn-outline-primary"
            data-testid="generate-manual-login-link"
            :disabled="generating"
            @click="createLink"
        >
            {{ generating ? 'Link wird erstellt …' : 'Anmeldelink erstellen' }}
        </button>

        <div
            v-if="createdLink"
            class="mt-3"
        >
            <label
                class="form-label"
                for="manual-login-link"
                >Einmaliger Anmeldelink</label
            >
            <div class="input-group">
                <input
                    id="manual-login-link"
                    ref="linkInput"
                    class="form-control"
                    data-testid="manual-login-url"
                    type="url"
                    :value="createdLink.url"
                    readonly
                    @focus="$event.target.select()"
                />
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-testid="copy-manual-login-link"
                    @click="copyLink"
                >
                    Link kopieren
                </button>
            </div>
            <p class="form-text">
                Gültig bis {{ expirationLabel }}. Nach der ersten Anmeldung ist der Link verbraucht.
            </p>
            <p
                v-if="copyStatus"
                class="form-text"
                role="status"
            >
                {{ copyStatus }}
            </p>
        </div>

        <p
            v-if="error"
            class="alert alert-danger mt-3"
            role="alert"
        >
            {{ error }}
        </p>
    </div>
</template>

<script>
import axios from 'axios'

/** Generate and copy a one-use, one-hour link for the selected user account.
 * @property {number} userId Target account identifier.
 */
export default {
    name: 'ManualLoginLinkControl',
    props: {
        userId: { type: Number, required: true },
    },
    /** Initialize transient URL state; the raw token is never persisted in page state. */
    data() {
        return {
            createdLink: null,
            generating: false,
            copyStatus: '',
            error: '',
        }
    },
    computed: {
        /** Format the UTC expiration instant in the application's Berlin time zone. */
        expirationLabel() {
            if (!this.createdLink?.expiresAt) {
                return ''
            }

            return new Intl.DateTimeFormat('de-DE', {
                dateStyle: 'medium',
                timeStyle: 'short',
                timeZone: 'Europe/Berlin',
            }).format(new Date(this.createdLink.expiresAt))
        },
    },
    methods: {
        /** Request one link and keep its raw URL only in this component's memory. */
        async createLink() {
            this.createdLink = null
            this.copyStatus = ''
            this.error = ''
            this.generating = true

            try {
                const response = await axios.post(
                    window.route('user.account.login-link', this.userId),
                    {},
                )
                if (!response.data?.manual_login_url || !response.data?.expires_at) {
                    throw new Error('Invalid manual login link response')
                }
                this.createdLink = {
                    url: response.data.manual_login_url,
                    expiresAt: response.data.expires_at,
                }
            } catch {
                this.error = 'Anmeldelink konnte nicht erstellt werden.'
            } finally {
                this.generating = false
            }
        },
        /** Copy the issued URL or select it for manual copying if clipboard access fails. */
        async copyLink() {
            this.copyStatus = ''

            try {
                if (!globalThis.navigator.clipboard?.writeText) {
                    throw new Error('Clipboard API unavailable')
                }
                await globalThis.navigator.clipboard.writeText(this.createdLink.url)
                this.copyStatus = 'Anmeldelink wurde kopiert.'
            } catch {
                this.$refs.linkInput?.focus()
                this.$refs.linkInput?.select()
                this.copyStatus = 'Automatisches Kopieren nicht möglich. Der Link ist markiert.'
            }
        },
    },
}
</script>

<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -
  -
  - Pfarrplaner is based on the Laravel framework (https://laravel.com).
  - This file may contain code created by Laravel's scaffolding functions.
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <section style="height: 100vh">
        <div class="container-fluid h-custom">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-md-9 col-lg-6 col-xl-5">
                    <img
                        src="/img/logo/pfarrplaner.svg"
                        class="img-fluid d-none d-md-inline"
                        alt="Pfarrplaner"
                    />
                </div>
                <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                    <h1 class="ps-0 pl-0 ms-0 ms-0 mb-4">{{ layout.appName }}</h1>
                    <h2
                        v-if="layout.appProvider"
                        class="ps-0 pl-0 ms-0 ms-0 mb-4"
                    >
                        &mdash; {{ layout.appProvider }} &mdash;
                    </h2>
                    <form
                        method="POST"
                        id="loginForm"
                        @submit.prevent.stop="submit"
                        novalidate
                    >
                        <FormCsrfToken />
                        <div v-if="!demo">
                            <p class="mb-2">Melde dich einfach und sicher mit einem Passkey an.</p>
                            <div class="d-grid gap-2 mt-3">
                                <button
                                    type="button"
                                    class="btn btn-primary btn-lg"
                                    data-testid="passkey-login"
                                    :disabled="passkeyLoading"
                                    @click="loginWithPasskey"
                                >
                                    <i
                                        class="fa fa-fingerprint me-2"
                                        aria-hidden="true"
                                    ></i>
                                    {{
                                        passkeyLoading
                                            ? 'Passkey wird geprüft …'
                                            : 'Mit Passkey anmelden'
                                    }}
                                </button>
                                <div class="text-center text-muted my-2">oder</div>
                                <FormInput
                                    v-if="!emailLinkRequested"
                                    v-model="form.email"
                                    name="email"
                                    label="E-Mailadresse"
                                    autofocus
                                    placeholder="deine@email.de"
                                    autocomplete="username"
                                    required
                                />
                                <button
                                    v-if="!emailLinkRequested"
                                    type="button"
                                    class="btn btn-outline-primary btn-lg"
                                    data-testid="email-login-link"
                                    :disabled="emailLinkLoading || !form.email"
                                    @click="requestEmailLink"
                                >
                                    <i
                                        class="fa fa-envelope me-2"
                                        aria-hidden="true"
                                    ></i>
                                    {{
                                        emailLinkLoading
                                            ? 'Anmeldelink wird angefordert …'
                                            : 'Anmeldelink per E-Mail erhalten'
                                    }}
                                </button>
                            </div>
                            <div
                                v-if="emailLinkRequested"
                                class="alert alert-success mt-3"
                                role="status"
                                data-testid="email-login-confirmation"
                            >
                                <h3 class="h5">Prüfe dein Postfach</h3>
                                <p class="mb-2">
                                    Falls die Adresse zu einem Konto gehört, das sich anmelden kann,
                                    wurde eine E-Mail mit dem Anmeldelink verschickt. Prüfe dein
                                    Postfach und gegebenenfalls den Spam-Ordner. Öffne den Link in
                                    der Nachricht und wähle anschließend auf der Bestätigungsseite
                                    „Jetzt anmelden“.
                                </p>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-testid="different-email-address"
                                    @click="useDifferentEmailAddress"
                                >
                                    Mit einer anderen E-Mail-Adresse anmelden
                                </button>
                            </div>
                            <p
                                v-if="
                                    $page.props.flash?.status &&
                                    !emailLinkRequested &&
                                    !emailLinkStatusDismissed
                                "
                                class="text-success mt-2"
                                role="status"
                            >
                                {{ $page.props.flash.status }}
                            </p>
                            <p
                                v-if="emailLinkError"
                                class="text-danger mt-2"
                                role="alert"
                            >
                                {{ emailLinkError }}
                            </p>
                        </div>

                        <div v-else>
                            <div class="form-outline mb-4">
                                <label
                                    class="form-label"
                                    for="users"
                                    >E-Mailadresse</label
                                >
                                <select
                                    id="users"
                                    name="email"
                                    class="form-control"
                                    v-model="form.email"
                                    aria-describedby="usersHelp"
                                >
                                    <option
                                        v-for="user in users"
                                        :key="user.email"
                                        :value="user.email"
                                    >
                                        {{ user.title ? user.title + ' ' : '' }}{{ user.name }} ({{
                                            user.email
                                        }})
                                    </option>
                                </select>
                                <small
                                    id="usersHelp"
                                    class="form-text text-muted"
                                    >Wählen Sie einen Demo-Benutzer aus.</small
                                >
                            </div>
                        </div>
                        <div
                            v-if="demo"
                            class="d-flex flex-wrap align-items-center gap-2 mt-4 pt-2"
                        >
                            <button
                                v-if="demo"
                                type="submit"
                                class="btn btn-primary btn-lg"
                                :disabled="loggingIn"
                                style="padding-left: 2.5rem; padding-right: 2.5rem"
                            >
                                Anmelden
                            </button>
                        </div>
                        <p
                            v-if="passkeyError"
                            class="text-danger mt-2"
                            role="alert"
                        >
                            {{ passkeyError }}
                        </p>
                    </form>
                    <DonationNotice class="mt-4 pt-3 border-top" />
                </div>
            </div>
        </div>
        <div
            class="d-flex flex-column flex-md-row text-center text-md-start justify-content-between py-4 px-4 px-xl-5 bg-primary"
        >
            <!-- Copyright -->
            <div class="text-white mb-3 mb-md-0">
                Copyright © <b>Pfarrplaner</b>. All rights reserved.
            </div>
            <!-- Copyright -->

            <!-- Right -->
            <div>
                <a
                    class="me-2 me-2 text-white"
                    :href="route('what.is')"
                    >Was ist der Pfarrplaner?</a
                >
                <a
                    href="https://pfarr.tools"
                    class="text-white"
                >
                    <i class="fa fa-wrench"></i> pfarr.tools
                </a>
            </div>
            <!-- Right -->
        </div>
    </section>
</template>

<script>
import FormInput from '../../components/Ui/forms/FormInput.vue'
import FormCsrfToken from '../../components/Ui/forms/FormCsrfToken.vue'
import DonationNotice from '../../components/Ui/DonationNotice.vue'
import { Passkeys } from '@laravel/passkeys'

export default {
    name: 'Login',
    components: { DonationNotice, FormCsrfToken, FormInput },
    computed: {
        layout() {
            return this.$page.props
        },
    },
    data() {
        return {
            csrf: null,
            dev: this.$page.props.dev,
            demo: this.$page.props.demo,
            users: this.$page.props.users || [],
            loggingIn: false,
            passkeyLoading: false,
            emailLinkLoading: false,
            emailLinkRequested: false,
            emailLinkStatusDismissed: false,
            passkeyError: '',
            emailLinkError: '',
            form: {
                email: this.$page.props.users?.[0]?.email || '',
            },
        }
    },
    /** Focus the email or demo-user input after Vue has rendered. */
    mounted() {
        this.$nextTick(() => this.focusCurrentInput())
    },
    methods: {
        /** Focus the input shown for this login mode. */
        focusCurrentInput() {
            const selector = this.demo ? '#users' : '[name="email"]'
            this.$el.querySelector(selector)?.focus()
        },
        /** Return from the sent-link confirmation to the email-address form. */
        useDifferentEmailAddress() {
            this.emailLinkRequested = false
            this.emailLinkStatusDismissed = true
            this.form.email = ''
            this.$nextTick(() => this.$el.querySelector('[name="email"]')?.focus())
        },
        /** Start passkey login for the entered email address. */
        async loginWithPasskey() {
            if (this.passkeyLoading || this.demo) return

            this.passkeyLoading = true
            this.passkeyError = ''
            try {
                const response = await Passkeys.verify({
                    routes: {
                        options: window.route('passkey.login-options'),
                        submit: window.route('passkey.login'),
                    },
                    remember: () => false,
                })
                if (response.redirect) window.location.assign(response.redirect)
            } catch (error) {
                this.passkeyError = this.passkeyErrorMessage(error)
            } finally {
                this.passkeyLoading = false
            }
        },
        /** Explain common browser and device failures without exposing server details. */
        passkeyErrorMessage(error) {
            if (!window.isSecureContext) {
                return 'Passkeys benötigen eine sichere Verbindung (HTTPS). Öffne Pfarrplaner über eine HTTPS-Adresse oder nutze den E-Mail-Link.'
            }

            switch (error?.name) {
                case 'NotSupportedError':
                    return 'Dieser Browser oder dieses Gerät unterstützt die Passkey-Anmeldung nicht.'
                case 'InvalidDomainError':
                    return 'Passkeys sind für diese Webadresse nicht verfügbar. Bitte öffne Pfarrplaner über die gewohnte Adresse.'
                case 'UserCancelledError':
                    return 'Der Browser oder Geräteschlüssel hat die Passkey-Anmeldung abgebrochen. Prüfe, ob auf diesem Gerät ein Passkey für Pfarrplaner verfügbar ist, oder melde dich per E-Mail-Link an.'
                case 'ServerError':
                    return error.status === 429
                        ? 'Zu viele Passkey-Versuche. Bitte warte kurz oder melde dich per E-Mail-Link an.'
                        : 'Die Passkey-Anmeldung konnte nicht abgeschlossen werden. Bitte versuche es erneut oder nutze den E-Mail-Link.'
                default:
                    return 'Die Passkey-Anmeldung konnte nicht abgeschlossen werden. Bitte versuche es erneut oder nutze den E-Mail-Link.'
            }
        },
        /** Request a passwordless sign-in link for the entered email address. */
        async requestEmailLink() {
            if (this.emailLinkLoading || this.demo) return
            this.emailLinkLoading = true
            this.emailLinkError = ''
            try {
                const response = await window.axios.get(window.route('csrf.keepalive'))
                this.$inertia.post(
                    window.route('login.link.request'),
                    { email: this.form.email, _token: response.data.token },
                    {
                        onSuccess: () => {
                            this.emailLinkRequested = true
                        },
                        onError: () => {
                            this.emailLinkError =
                                'Der Anmeldelink konnte nicht angefordert werden. Bitte prüfen Sie die E-Mailadresse.'
                        },
                        onFinish: () => {
                            this.emailLinkLoading = false
                        },
                    },
                )
            } catch {
                this.emailLinkError =
                    'Die Anmeldung konnte gerade nicht vorbereitet werden. Bitte lade die Seite neu und versuche es erneut.'
                this.emailLinkLoading = false
            }
        },
        /** Submit the demo account selector form. */
        submit(event) {
            if (this.loggingIn || !this.demo) return
            this.loggingIn = true
            const form = event.currentTarget
            window.axios
                .get(window.route('csrf.keepalive'))
                .then((response) => {
                    const data = new window.FormData(form)
                    data.set('_token', response.data.token)
                    this.$inertia.post(window.route('login'), data, {
                        preserveState: true,
                        onFinish: () => {
                            this.loggingIn = false
                        },
                    })
                })
                .catch(() => {
                    this.loggingIn = false
                })
        },
    },
}
</script>

<style scoped>
.divider:after,
.divider:before {
    content: '';
    flex: 1;
    height: 1px;
    background: #eee;
}

.h-custom {
    height: calc(100% - 73px);
}

@media (max-width: 450px) {
    .h-custom {
        height: 100%;
    }
}
</style>

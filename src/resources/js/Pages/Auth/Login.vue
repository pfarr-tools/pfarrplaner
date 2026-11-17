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
                        <!-- Email input -->
                        <div v-if="!demo && loginStep === 1">
                            <FormInput
                                v-model="form.email"
                                name="email"
                                label="E-Mailadresse"
                                autofocus
                                placeholder="deine@email.de"
                                autocomplete="username"
                                required
                            />
                            <button
                                type="button"
                                class="btn btn-primary btn-lg mt-3"
                                data-testid="continue-login"
                                @click="continueLogin"
                            >
                                Weiter
                            </button>
                        </div>

                        <div v-else-if="!demo">
                            <input
                                type="hidden"
                                name="email"
                                :value="form.email"
                            />
                            <p>
                                Weiter mit <strong>{{ form.email }}</strong>
                            </p>
                            <button
                                type="button"
                                class="btn btn-link px-0"
                                @click="loginStep = 1"
                            >
                                Andere E-Mailadresse verwenden
                            </button>
                            <FormInput
                                class="my-3"
                                type="password"
                                name="password"
                                v-model="form.password"
                                label="Passwort"
                                autocomplete="current-password"
                                autofocus
                                required
                            />
                            <FormCheck
                                v-model="form.remember"
                                label="Angemeldet bleiben"
                            />
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
                            v-if="demo || loginStep === 2"
                            class="text-end text-lg-start mt-4 pt-2"
                            :key="attempts"
                        >
                            <button
                                type="submit"
                                class="btn btn-primary btn-lg"
                                :disabled="loggingIn"
                                style="padding-left: 2.5rem; padding-right: 2.5rem"
                            >
                                Anmelden
                            </button>
                        </div>
                        <div
                            v-if="!demo"
                            class="mt-3"
                        >
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                data-testid="passkey-login"
                                :disabled="passkeyLoading"
                                @click="loginWithPasskey"
                            >
                                {{
                                    passkeyLoading
                                        ? 'Passkey wird geprüft …'
                                        : 'Mit Passkey anmelden'
                                }}
                            </button>
                            <p
                                v-if="passkeyError"
                                class="text-danger mt-2"
                                role="alert"
                            >
                                {{ passkeyError }}
                            </p>
                        </div>
                        <div
                            v-if="!demo"
                            class="mt-3"
                        >
                            <a
                                :href="route('password.request')"
                                data-testid="forgot-password"
                            >
                                Passwort vergessen?
                            </a>
                        </div>
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
import FormCheck from '../../components/Ui/forms/FormCheck.vue'
import FormCsrfToken from '../../components/Ui/forms/FormCsrfToken.vue'
import DonationNotice from '../../components/Ui/DonationNotice.vue'
import { Passkeys } from '@laravel/passkeys'

export default {
    name: 'Login',
    components: { DonationNotice, FormCsrfToken, FormCheck, FormInput },
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
            attempts: 0,
            loginStep: this.$page.props.demo ? 2 : 1,
            loggingIn: false,
            passkeyLoading: false,
            passkeyError: '',
            form: {
                email: this.$page.props.users?.[0]?.email || '',
                password: '',
                remember: false,
            },
        }
    },
    methods: {
        /** Advance locally so every username receives the same next screen. */
        continueLogin() {
            if (!this.form.email.trim()) return
            this.loginStep = 2
        },
        /** Start discoverable passkey login without sending the typed username. */
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
                    remember: () => this.form.remember,
                })
                if (response.redirect) window.location.assign(response.redirect)
            } catch {
                this.passkeyError =
                    'Passkey-Anmeldung fehlgeschlagen. Bitte erneut versuchen oder Passwort verwenden.'
            } finally {
                this.passkeyLoading = false
            }
        },
        /** Keep Enter on the username step local; submit credentials on step two. */
        submit(event) {
            if (this.loggingIn) return
            if (!this.demo && this.loginStep === 1) {
                this.continueLogin()
                return
            }
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

<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -
  - Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
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
    <section style="height: 100vh;">
        <div class="container-fluid h-custom">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-md-9 col-lg-6 col-xl-5">
                    <img src="/img/logo/pfarrplaner.svg" class="img-fluid d-none d-md-inline" alt="Pfarrplaner">
                </div>
                <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                    <h1 class="ps-0 pl-0 ms-0 ms-0 mb-4">{{ layout.appName }}</h1>
                    <h2 v-if="layout.appProvider" class="ps-0 pl-0 ms-0 ms-0 mb-4">&mdash; {{ layout.appProvider }} &mdash;</h2>
                    <form method="POST" id="loginForm" @submit.prevent.stop="submit">
                        <form-csrf-token />
                        <!-- Email input -->
                        <div v-if="!demo">
                            <form-input v-if="!demo"
                                v-model="form.email"
                                name="email" label="E-Mailadresse" autofocus placeholder="deine@email.de"/>
                            <form-input class="my-3"
                                        type="password" name="password" v-model="form.password"
                                        label="Passwort" />
                            <form-check v-model="form.remember" label="Angemeldet bleiben" />

                        </div>

                        <div v-else>
                            <div class="form-outline mb-4">
                                <label class="form-label" for="form3Example3">E-Mailadresse</label>

                                <select id="users" name="email" class="form-control">
                                    <option v-for="user in users" :key="user.email" :value="user.email">
                                        {{ user.title ? user.title+' ' : ''}}{{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="text-end text-lg-start mt-4 pt-2" :key="attempts">
                            <button  class="btn btn-primary btn-lg"
                                     @click="submit"
                                     style="padding-left: 2.5rem; padding-right: 2.5rem;">Anmelden</button>
                        </div>


                    </form>
                </div>
            </div>
        </div>
        <div
            class="d-flex flex-column flex-md-row text-center text-md-start justify-content-between py-4 px-4 px-xl-5 bg-primary">
            <!-- Copyright -->
            <div class="text-white mb-3 mb-md-0">
                Copyright © <b>Pfarrplaner</b>. All rights reserved.
            </div>
            <!-- Copyright -->

            <!-- Right -->
            <div>
                <a class="me-2 me-2 text-white" :href="route('what.is')">Was ist der Pfarrplaner?</a>
                <a href="https://pfarr.tools" class="text-white">
                    <i class="fa fa-wrench"></i> pfarr.tools
                </a>
            </div>
            <!-- Right -->
        </div>
    </section>

</template>


<script>
import FormInput from "../../components/Ui/forms/FormInput.vue";
import FormCheck from "../../components/Ui/forms/FormCheck.vue";
import FormCsrfToken from "../../components/Ui/forms/FormCsrfToken.vue";

export default {
    name: "Login",
    components: {FormCsrfToken, FormCheck, FormInput},
    computed: {
        layout() {
            return this.$page.props;
        }
    },
    data() {
        return {
            csrf: null,
            dev: this.$page.props.dev,
            attempts: 0,
            loggingIn: false,
            form: {
                email: '',
                password: '',
                remember: false,
            }
        };
    },
    methods: {
        submit() {
            if (this.loggingIn) return;
            this.loggingIn = true;
            axios.get(route('csrf.keepalive')).then(response => {
                this.form._token = response.data.token;
                this.$inertia.post(route('login'), this.form, {preserveState: false});
            })
        }
    },

}
</script>


<style scoped>
.divider:after,
.divider:before {
    content: "";
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

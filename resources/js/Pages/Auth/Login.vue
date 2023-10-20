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
                    <form method="POST" :action="route('login')" id="loginForm">
                        <input type="hidden" name="_token" :value="csrf" :key="csrf">
                        <div v-if="!demo">
                            <!-- Email input -->
                            <div class="form-outline mb-4">
                                <label class="form-label" for="form3Example3">E-Mailaddresse</label>
                                <input type="email" name="email" class="form-control form-control-lg"
                                       value=""
                                       placeholder="deine@email.de" autofocus/>
                            </div>

                            <!-- Password input -->
                            <div class="form-outline mb-3">
                                <label class="form-label" for="form3Example4">Passwort</label>
                                <input type="password" name="password" class="form-control form-control-lg"
                                       placeholder="Dein Passwort"/>
                            </div>
                        </div>
                        <div v-else>
                            <div class="form-outline mb-4">
                                <label class="form-label" for="form3Example3">E-Mailaddresse</label>

                                <select id="users" name="email" class="form-control">
                                    <select id="users" name="email" class="form-control" :value="users[0].email"
                                    <option v-for="user in users" :value="user.email">
                                        {{ user.title ? user.title+' ' : ''}}{{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Checkbox -->
                            <div class="form-check mb-0">
                                <input class="form-check-input me-2" type="checkbox" value="1" name="remember"/>
                                <label class="form-check-label" for="form2Example3">
                                    Angemeldet bleiben
                                </label>
                            </div>
                        </div>

                        <div class="text-end text-lg-start mt-4 pt-2">
                            <input type="submit" class="btn btn-primary btn-lg"
                                   style="padding-left: 2.5rem; padding-right: 2.5rem;" value="Anmelden"/>
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
export default {
    name: "Login",
    props: ['users', 'demo'],
    computed: {
        layout() {
            return this.$page.props;
        }
    },
    data() {
        return {
            dev: this.$page.props.dev,
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            lastRefresh: -1,
        };
    },
    mounted() {
        if (this.lastRefresh == -1) this.refreshToken();
        setTimeout(this.refreshToken, 600000);
    },
    methods: {
        refreshToken() {
            axios.get(route('csrf.keepalive')).then(response => {
                this.csrf = response.data.token;
                axios.defaults.headers.common['X-CSRF-TOKEN'] = this.csrf;
                document.querySelector('meta[name="csrf-token"]').setAttribute('content', this.csrf);
                this.lastRefresh = (new Date()).getTime();
            })
        },
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

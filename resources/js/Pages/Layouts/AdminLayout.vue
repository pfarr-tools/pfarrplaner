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
    <!--begin::App Wrapper-->
    <div class="admin-layout app-wrapper">
        <!--begin::Header-->
        <nav class="app-header navbar navbar-expand bg-body py-2">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Start Navbar Links-->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" @click.prevent.stop="toggleSideBar(!collapsed)">
                            <i class="mdi mdi-menu"></i>
                        </a>
                    </li>
                    <slot name="navbar-left" />
                </ul>
                <!--end::Start Navbar Links-->

                <!--begin::End Navbar Links-->
                <ul class="navbar-nav ms-auto">
                    <slot name="navbar-right" />

                    <li class="nav-item dropdown" v-if="enableControlSidebar">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="mdi mdi-cog"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <div class="p-2">
                                <slot name="control-sidebar" />
                            </div>
                        </ul>
                    </li>

                    <li v-if="layout.adminUserSwitchBack" class="nav-item">
                        <a class="btn btn-warning mr-1" :href="route('user.switchback')">
                            <i class="mdi mdi-account-switch"></i>
                        </a>
                    </li>

                    <!--begin::User Menu Dropdown-->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i v-if="!user.image" class="nav-icon mdi mdi-account" ></i>
                            <img v-else class="rounded-circle" :src="user.image.replace('attachments/', '/image/')" width="22" height="22" />
                            <span class="d-none d-md-inline">{{ user.name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <!--begin::User Image-->
                            <li class="user-header text-bg-primary">
                                <i v-if="!user.image" class="nav-icon mdi mdi-account" style="font-size: 4em;"></i>
                                <img v-else class="rounded-circle" :src="user.image.replace('attachments/', '/image/')" width="22" height="22" />

                                <p>
                                    {{ user.name }}
                                </p>
                            </li>
                            <!--end::User Image-->
                            <!--begin::Menu Body-->
                            <li class="user-body">
                                <inertia-link :href="route('user.profile')" class="btn btn-default btn-flat">Einstellungen</inertia-link>
                                <inertia-link :href="route('logout')" class="btn btn-default btn-flat float-end">Abmelden</inertia-link>
                            </li>
                            <!--end::Menu Body-->
                            <!--begin::Menu Footer-->
                            <!--end::Menu Footer-->
                        </ul>
                    </li>
                    <!--end::User Menu Dropdown-->

                </ul>
                <!--end::End Navbar Links-->
            </div>
            <!--end::Container-->
        </nav>
        <!--end::Header-->
        <!--begin::Sidebar-->
        <aside class="app-sidebar shadow" :class="dev ? 'bg-info' : 'bg-primary'" data-bs-theme="dark">
            <!--begin::Sidebar Brand-->
            <div class="sidebar-brand">
                <!--begin::Brand Link-->
                <inertia-link href="/" class="brand-link">
                    <!--begin::Brand Image-->
                    <img src="/img/logo/pfarrplaner.svg" class="brand-image opacity-75 shadow" :title="'Startseite (Pfarrplaner '+package.info.version+'-'+package.env+', '+moment(package.date).locale('de').format('LLLL')+')'">
                    <!--end::Brand Image-->
                    <!--begin::Brand Text-->
                    <span class="brand-text fw-light">{{ layout.appName }}</span>
                    <!--end::Brand Text-->
                </inertia-link>
                <!--end::Brand Link-->
                <a class="d-inline d-md-none ms-2" @click.prevent.stop="toggleSideBar(true)"><i class="mdi mdi-chevron-left-circle"></i></a>
            </div>
            <!--end::Sidebar Brand-->
            <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper" >
                <nav class="mt-2" v-if="!noNavBar">
                    <!--begin::Sidebar Menu-->
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                        <li v-for="item in layout.menu" :class="{
                            'nav-header': (item.text == undefined) ,
                            'nav-item': (item.text != undefined),
                        }">
                            {{ item.text == undefined ? item.toUpperCase() : '' }}
                            <inertia-link v-if="item.text && item.inertia" class="nav-link" :class="{ active: item.active }" :href="item.url">
                                <i v-if="item.icon && (!item.profile)" class="nav-icon" :class="item.icon"  :style="{ color: item.icon_color || 'inherit'}"></i>
                                <p v-if="item.text">
                                    {{ item.text }}
                                </p>
                            </inertia-link>
                            <a v-if="item.text && (!item.inertia)" class="nav-link" :class="{ active: item.active }" :href="item.url">
                                <i v-if="item.icon && (!item.profile)" class="nav-icon" :class="item.icon"  :style="{ color: item.icon_color || 'inherit'}"></i>
                                <p v-if="item.text">
                                    {{ item.text }}
                                </p>
                            </a>
                        </li>


                    </ul>
                    <!--end::Sidebar Menu-->
                </nav>
            </div>
            <!--end::Sidebar Wrapper-->
        </aside>
        <!--end::Sidebar-->
        <!--begin::App Main-->
        <main class="app-main">
            <!--begin::App Content Header-->
            <div class="app-content-header">
                <!--begin::Container-->
                <div class="container-fluid mb-0 pb-0">
                    <h1 v-if="title" class="m-0 mb-4 text-dark" :key="title">{{ title }}</h1>
                    <!-- flash messages here -->
                    <slot name="before-flash" />
                    <div v-if="(layout.errors.length > 0) || layout.flash.error" class="alert alert-danger">
                        <span v-if="layout.flash.error">{{ layout.flash.error }}</span>
                        <span v-else>Dein Formular enthält {{ layout.errors.length }} Fehler. Bitte überprüfe deine Eingaben.</span>
                    </div>
                    <div v-for="flashType in ['success','info']">
                        <div v-if="layout.flash[flashType]" class="alert" :class="'alert-'+flashType">{{ layout.flash[flashType] }}</div>
                    </div>
                    <slot name="after-flash" />
                    <div class="slot-tab-headers mb-0 pb-0">
                        <slot name="tab-headers" />
                    </div>
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content Header-->
            <!--begin::App Content-->
            <div class="app-content" :class="{'p-0': noPadding, 'pt-3': !noPadding}">
                <div class="container-fluid" :class="{'p-0': noPadding}">
                    <slot/>
                </div>
            </div>
            <!--end::App Content-->
        </main>
        <!--end::App Main-->

    </div>
    <!--end::App Wrapper-->
</template>

<script>

import '../../../sass/adminlte.scss';
import 'admin-lte/dist/js/adminlte.min';

export default {
    props: {
        'enableControlSidebar': {
            default: false,
        },
        'noNavBar': {
            default: false,
        },
        'title': {
            default: '',
        },
        noContentHeader: Boolean,
        noPadding: Boolean,
    },
    computed: {
        layout() {
            return this.$page.props;
        }
    },
    mounted() {
        if (this.title != '') document.title = this.title + ' :: ' + this.layout.appName;
    },
    data() {
        return {
            dev: this.$page.props.dev,
            package: this.$page.props.package,
            user: this.$page.props.currentUser.data,
            collapsed: true,
        };
    },
    methods: {
        clickUrl(url) {
            if (url == '#') return;
            window.location.href = url;
        },
        toggleSideBar(state) {
            this.collapsed = state;
            let e = document.querySelector('body');
            if (this.collapsed) {
                e.classList.remove('sidebar-open');
                e.classList.add('sidebar-collapse');
            } else {
                e.classList.remove('sidebar-collapse');
                e.classList.add('sidebar-open');
            }
        },
    }
}
</script>

<style scoped>
.nprogress-busy .admin-layout {
    margin-top: 2px;
}

.sidebar-brand .brand-link {
    align-items: left !important;
}

</style>

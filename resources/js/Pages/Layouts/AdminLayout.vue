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
    <div
        class="admin-layout"
        :class="{ 'sidebar-pinned': sidebarPinned }"
    >
        <!-- Desktop Sidebar -->
        <Sidebar
            v-if="!mobileMenuOpen"
            class="d-none d-lg-flex"
            :pinned="sidebarPinned"
            :hovered="sidebarHovered"
            @hover="sidebarHovered = $event"
            @pin="togglePin"
        ></Sidebar>

        <!-- Mobile Overlay Sidebar -->
        <transition name="slide">
            <Sidebar
                v-if="mobileMenuOpen"
                class="d-lg-none position-fixed top-0 start-0 w-100 h-100 z-0"
                :mobile="true" :layout="layout" :package="package"
                @close="mobileMenuOpen = false"
                :appName="layout.appName"
                :menu="layout.menu"
            ></Sidebar>
        </transition>

        <div class="admin-main d-flex flex-column">
            <Topbar @toggleMobileMenu="toggleMobileMenu"
                    :mobileMenuOpen = "mobileMenuOpen"
                    :enableControlSidebar="enableControlSidebar"
            >
                <template v-slot:navbar-left>
                    <slot name="navbar-left"></slot>
                </template>
                <template v-slot:navbar-right>
                    <slot name="navbar-right"></slot>
                </template>
                <template v-slot:control-sidebar>
                    <slot name="control-sidebar"></slot>
                </template>
            </Topbar>

            <div class="px-2 pt-4 d-flex flex-column flex-grow-1 min-vh-0">
                <h1 v-if="title" class="m-0 mb-4 text-dark" :key="title">{{ title }}</h1>
                <slot name="before-flash" />
                <transition name="fade">
                    <FlashMessage
                        v-if="flash.message"
                        :type="flash.type"
                        :message="flash.message"
                    ></FlashMessage>
                </transition>
                <slot name="after-flash" />
                <div class="slot-tab-headers mt-3 mb-0 pb-0">
                    <slot name="tab-headers" />
                </div>

                <main class="admin-content flex-grow-1 overflow-auto p-3 min-vh-0">
                    <slot></slot>
                </main>
            </div>
        </div>
    </div>
</template>

<script>
import Sidebar from './Parts/Sidebar.vue'
import Topbar from './Parts/Topbar.vue'
import FlashMessage from './Parts/FlashMessage.vue'

export default {
    name: 'AdminLayout',
    components: { Sidebar, Topbar, FlashMessage },
    props: {
        flash: { type: Object, default: () => ({}) },
        'enableControlSidebar': {
            type: Boolean,
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
    mounted() {
        if (this.title != '') document.title = this.title + ' :: ' + this.layout.appName;
        window.token();
    },
    data() {
        return {
            sidebarPinned: false,
            sidebarHovered: false,
            mobileMenuOpen: false,
            dev: this.$page.props.dev,
            package: this.$page.props.package,
            user: this.$page.props.currentUser.data,
            collapsed: true,
            layout: this.$page.props,
        }
    },
    created() {
        // Pin-Status beim Laden aus localStorage lesen
        const saved = localStorage.getItem('pfarrplaner_sidebar_pinned')
        if (saved === 'true') {
            this.sidebarPinned = true
        }
    },
    methods: {
        togglePin() {
            this.sidebarPinned = !this.sidebarPinned
            // Pin-Status speichern
            localStorage.setItem('pfarrplaner_sidebar_pinned', this.sidebarPinned)
        },
        toggleMobileMenu() {
            this.mobileMenuOpen = !this.mobileMenuOpen
        }
    }
}
</script>

<style scoped>
:root {
    --sidebar-collapsed: 70px;
    --sidebar-expanded: 240px;
}

.admin-layout {
    display: flex;
    height: 100vh;
    overflow: hidden;
}

.admin-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    transition: margin-left 0.3s ease;
    margin-left: var(--sidebar-collapsed);
}

.admin-layout.sidebar-pinned .admin-main {
    margin-left: var(--sidebar-expanded);
}

.admin-content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

/* Animationen */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}
.slide-enter {
    transform: translateX(-100%);
}
.slide-leave-to {
    transform: translateX(-100%);
}

.min-vh-0 {
    min-height: 0 !important;
}
</style>

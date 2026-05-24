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
    <aside
        v-if="mobile"
        class="shadow-lg h-100 w-75 position-absolute top-0 start-0 p-3 d-flex flex-column"
        :class="dev ? 'bg-info' : 'bg-primary'"
    >
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
            <strong>Menü</strong>
            <button class="btn btn-sm btn-outline-secondary" @click="$emit('close')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <ul class="nav flex-column">
            <li v-for="item in menu" :class="{
                            'nav-header': (item.text == undefined) ,
                            'nav-item': (item.text != undefined),
                        }">
                <div v-if="item.text === undefined" class="px-4 p-2 text-start text-uppercase">{{  item }}</div>
                <inertia-link v-if="item.text && item.inertia" class="px-4 p-2 text-start w-100 nav-link" :class="{ active: item.active }" :href="item.url">
                    <i v-if="item.icon && (!item.profile)" class="nav-icon" :class="item.icon"  :style="{ color: item.icon_color || 'inherit'}"></i>
                    <span v-if="item.text">
                        {{ item.text }}
                    </span>
                </inertia-link>
                <a v-if="item.text && (!item.inertia)" class="px-4 p-2 text-start w-100 nav-link" :class="{ active: item.active }" :href="item.url">
                    <i v-if="item.icon && (!item.profile)" class="nav-icon" :class="item.icon"  :style="{ color: item.icon_color || 'inherit'}"></i>
                    <span v-if="item.text">
                        {{ item.text }}
                    </span>
                </a>
            </li>
        </ul>
        <div class="mt-auto pt-3 border-top sidebar-support">
            <button
                type="button"
                class="px-4 py-2 text-start w-100 nav-link d-flex align-items-center gap-2 border-0 bg-transparent"
                @click="toggleSupportMenu($event)"
            >
                <span aria-hidden="true">❤️</span>
                <span>Pfarrplaner unterstützen</span>
            </button>
        </div>
    </aside>

    <aside
        v-else
        class="admin-sidebar border-end shadow-sm d-flex flex-column p-0 m-0"
        :class="{ 'expanded': expanded, 'bg-info': dev, 'bg-primary': !dev }"
        @mouseenter="setHover(true)"
        @mouseleave="setHover(false)"
    >
        <div class="p-0 m-0 d-flex justify-content-between align-items-center border-bottom brand-container">
            <inertia-link href="/" class="px-3 text-decoration-none fw-bold brand-link">
                <!--begin::Brand Image-->
                <img src="/img/logo/pfarrplaner.svg" class="brand-image opacity-75 shadow" :title="'Startseite (Pfarrplaner '+appInfo+')'">
                <!--end::Brand Image-->
                <!--begin::Brand Text-->
                <span v-if="expanded" class="px-2 brand-text">{{ appName }}</span>
                <!--end::Brand Text-->
            </inertia-link>

            <button v-if="expanded"
                @click="$emit('pin')"
                class="btn btn-sm me-2 btn-outline-light"
                :title="pinned ? 'Klicken, um Seitenleiste automatisch zu öffnen und zu schließen' : 'Klicken, um Seitenleiste anzuheften'"
            >
                <i :class="pinned ? 'mdi mdi-pin-off-outline' : 'mdi mdi-pin-outline'"></i>
            </button>
        </div>

        <ul class="nav flex-column p-0 flex-grow-1 overflow-auto text-center">
            <li v-for="item in menu" :class="{
                            'nav-header': (item.text == undefined) ,
                            'nav-item': (item.text != undefined),
                        }">
                <div v-if="expanded && (item.text === undefined)" class="px-4 p-2 text-start text-uppercase">{{  item }}</div>
                <inertia-link v-if="item.text && item.inertia" class="px-4 p-2 text-start w-100 nav-link" :class="{ active: item.active }" :href="item.url">
                    <i v-if="item.icon && (!item.profile)" class="nav-icon" :class="item.icon"  :style="{ color: item.icon_color || 'inherit'}"></i>
                    <span v-if="expanded && item.text" class="ms-1">
                        {{ item.text }}
                    </span>
                </inertia-link>
                <a v-if="item.text && (!item.inertia)" class="px-4 p-2 text-start w-100 nav-link" :class="{ active: item.active }" :href="item.url">
                    <i v-if="item.icon && (!item.profile)" class="nav-icon" :class="item.icon"  :style="{ color: item.icon_color || 'inherit'}"></i>
                    <span v-if="expanded && item.text" class="ms-1">
                        {{ item.text }}
                    </span>
                </a>
            </li>
        </ul>
        <div class="sidebar-support border-top">
            <button
                type="button"
                class="px-4 py-3 text-start w-100 nav-link d-flex align-items-center border-0 bg-transparent"
                @click="toggleSupportMenu($event)"
            >
                <span aria-hidden="true">❤️</span>
                <span v-if="expanded" class="ms-2">Pfarrplaner unterstützen</span>
            </button>
        </div>
    </aside>

    <div
        v-if="supportMenuOpen"
        ref="supportPopup"
        class="support-popup card shadow"
        :style="supportMenuStyle"
    >
        <div class="card-body p-2">
            <a
                class="support-popup-link"
                href="https://paypal.me/potofcoffee"
                target="_blank"
                rel="noopener noreferrer"
                @click="closeSupportMenu"
            >
                Einmalig per PayPal
            </a>
            <a
                class="support-popup-link"
                href="https://liberapay.com/christoph.fischer"
                target="_blank"
                rel="noopener noreferrer"
                @click="closeSupportMenu"
            >
                Regelmäßig per Liberapay
            </a>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Sidebar',
    props: {
        pinned: Boolean,
        hovered: Boolean,
        mobile: Boolean,
    },
    data() {
        return {
            supportMenuOpen: false,
            supportMenuStyle: {},
            supportMenuButton: null,
        }
    },
    computed: {
        expanded() {
            return this.pinned || this.hovered
        },
        appInfo() {
            return this.$page.props.package.info.version+'-'+this.$page.props.package.env+', '+moment(this.$page.props.package.date).locale('de').format('LLLL')
        },
        menu() {
            return this.$page.props.menu;
        },
        appName() {
            return this.$page.props.appName;
        },
        dev() {
            return this.$page.props.dev;
        }
    },
    mounted() {
        document.addEventListener('click', this.handleDocumentClick)
        window.addEventListener('resize', this.closeSupportMenu)
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleDocumentClick)
        window.removeEventListener('resize', this.closeSupportMenu)
    },
    methods: {
        closeSupportMenu() {
            this.supportMenuOpen = false
            this.supportMenuButton = null
        },
        handleDocumentClick(event) {
            if (!this.supportMenuOpen) return

            const clickedButton = this.supportMenuButton?.contains(event.target)
            const clickedPopup = this.$refs.supportPopup?.contains(event.target)

            if (!clickedButton && !clickedPopup) {
                this.closeSupportMenu()
            }
        },
        setHover(state) {
            this.$emit('hover', state)
        },
        toggleSupportMenu(event) {
            if (this.supportMenuOpen) {
                this.closeSupportMenu()
                return
            }

            const rect = event.currentTarget.getBoundingClientRect()
            const popupWidth = 240
            const left = Math.min(
                Math.max(12, rect.left),
                window.innerWidth - popupWidth - 12
            )

            this.supportMenuButton = event.currentTarget
            this.supportMenuStyle = {
                top: `${rect.top - 8}px`,
                left: `${left}px`,
                width: `${popupWidth}px`,
                transform: 'translateY(-100%)',
            }
            this.supportMenuOpen = true
        }
    }
}
</script>

<style scoped lang="scss">

@use '../../../../sass/theme' as theme;

aside {
    z-index: 800;
}

.admin-sidebar {
    width: var(--sidebar-collapsed);
    height: 100vh;
    flex-shrink: 0;
    transition: width 0.3s ease;
    position: sticky;
    top: 0;
    overflow: hidden;
    color: white;

}

.admin-sidebar.expanded {
    width: var(--sidebar-expanded);
}

.brand-container {
    padding: 0 !important;
    margin: 0 !important;
    width: 100%;
    max-height: 58px;
    min-height: 58px;
    height: 58px;
    background-color: theme.elkw-color("violett");
}


.brand-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 3.5rem;
    padding: 0.8125rem 0.5rem;
    overflow: hidden;
    font-size: 1.25rem;
    white-space: nowrap;
    transition: width 0.3s ease-in-out;
}

.brand-link span {
    max-width: fit-content !important;
    visibility: visible !important;
    color: white;
}

.brand-link img {
    float: left;
    width: auto;
    max-height: 33px;
    line-height: 0.8;
}

a.nav-link,
button.nav-link {
    color: #c2c7d0;
}

a.nav-link:hover,
button.nav-link:hover {
    color: white;
    background-color: rgb(152.28, 165.24, 191.64);
}

.sidebar-support {
    flex-shrink: 0;
}

.support-popup {
    position: fixed;
    z-index: 1100;
    min-width: 220px;
}

.support-popup-link {
    display: block;
    padding: 0.5rem 0.75rem;
    color: #212529;
    text-decoration: none;
    border-radius: 0.375rem;
}

.support-popup-link:hover {
    background-color: #f1f3f5;
}

</style>

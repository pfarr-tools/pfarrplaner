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
    </aside>
</template>

<script>
export default {
    name: 'Sidebar',
    props: {
        pinned: Boolean,
        hovered: Boolean,
        mobile: Boolean,
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
    methods: {
        setHover(state) {
            this.$emit('hover', state)
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

a.nav-link {
    color: #c2c7d0;
}

a.nav-link:hover {
    color: white;
    background-color: rgb(152.28, 165.24, 191.64);
}

</style>

<template>
    <nav class="navbar navbar-light bg-white shadow-sm px-3 sticky-top">


        <button
            class="btn btn-light-outline d-lg-none me-2"
            @click="$emit('toggleMobileMenu')"
        >
            <i class="mdi" :class="mobileMenuOpen ? 'mdi-menu-open' : 'mdi-menu'"></i>
        </button>

        <!-- left navbar slot -->
        <div class="d-flex align-items-center me-auto">
            <slot name="navbar-left"></slot>
        </div>



        <div class="d-flex align-items-center ms-auto">
            <slot name="navbar-right"></slot>
            <div class="nav-item dropdown" v-if="enableControlSidebar">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="mdi mdi-cog"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <div class="p-2">
                        <slot name="control-sidebar" />
                    </div>
                </ul>
            </div>

            <div v-if="adminUserSwitchBack" class="nav-item">
                <a class="btn btn-warning mr-1" :href="route('user.switchback')">
                    <i class="mdi mdi-account-switch"></i>
                </a>
            </div>

            <!-- Help button -->
            <div class="nav-item">
                <a class="btn btn-sm btn-outline-secondary"
                   :href="helpUrl"
                   target="_blank"
                   rel="noopener"
                   title="Hilfe öffnen">
                    <i class="mdi mdi-help-circle-outline"></i>
                    <span class="d-none d-lg-inline ms-1">Hilfe</span>
                </a>
            </div>

            <!--begin::User Menu Dropdown-->
            <div class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <i v-if="!user.image" class="nav-icon mdi mdi-account" ></i>
                    <img v-else class="rounded-circle" :src="user.image.replace('attachments/', '/image/')" width="22" height="22" />
                    <span class="d-none d-md-inline">{{ user.name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <!--begin::User Image-->
                    <li class="p-2 user-header text-bg-primary">
                        <i v-if="!user.image" class="nav-icon mdi mdi-account" style="font-size: 4em;"></i>
                        <img v-else class="rounded-circle" :src="user.image.replace('attachments/', '/image/')" width="22" height="22" />

                        <p>
                            {{ user.name }}
                        </p>
                    </li>
                    <!--end::User Image-->
                    <!--begin::Menu Body-->
                    <li class="p-2 user-body">
                        <inertia-link :href="route('user.profile')" class="btn btn-default btn-flat">Einstellungen</inertia-link>
                        <inertia-link :href="route('logout')" class="btn btn-default btn-flat float-end">Abmelden</inertia-link>
                    </li>
                    <!--end::Menu Body-->
                    <!--begin::Menu Footer-->
                    <!--end::Menu Footer-->
                </ul>
            </div>
        </div>
    </nav>
</template>

<script>
export default {
    name: 'Topbar',
    computed: {
        user() {
            return this.$page.props.currentUser.data;
        },
        adminUserSwitchBack() {
            return this.$page.props.adminUserSwitchBack;
        },
        helpPage() {
            return this.$page.props.helpPage ?? 'index';
        },
        helpUrl() {
            const baseUrl = (this.$page.props.manualBaseUrl ?? 'https://handbuch.pfarrplaner.de').replace(/\/+$/, '');
            const helpPage = this.helpPage === 'index' ? '' : `${this.helpPage}/`;

            return `${baseUrl}/${helpPage}`;
        },
    },
    props: {
        enableControlSidebar: Boolean,
        mobileMenuOpen: Boolean,
    }
}
</script>

<style scoped>

.nav-item {
    margin: 0 .25em;
}

.nav-item hover {
    background-color: lightgray;
}
</style>

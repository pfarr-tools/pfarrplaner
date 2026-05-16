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
            <div class="nav-item dropdown" v-if="enableControlSidebar" data-bs-auto-close="outside">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary topbar-action-button topbar-dropdown-toggle"
                        data-bs-toggle="dropdown"
                        aria-label="Seiteneinstellungen öffnen"
                        title="Seiteneinstellungen">
                    <span class="mdi mdi-cog"></span>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end topbar-settings-menu p-0">
                    <div class="p-2">
                        <slot name="control-sidebar" />
                    </div>
                </div>
            </div>

            <div v-if="adminUserSwitchBack" class="nav-item">
                <a class="btn btn-warning mr-1" :href="route('user.switchback')">
                    <i class="mdi mdi-account-switch"></i>
                </a>
            </div>

            <!-- Help button -->
            <div class="nav-item">
                <a class="btn btn-sm btn-outline-secondary topbar-action-button"
                   :href="helpUrl"
                   target="_blank"
                   rel="noopener"
                   title="Hilfe öffnen">
                    <i class="mdi mdi-help-circle-outline"></i>
                </a>
            </div>

            <!--begin::User Menu Dropdown-->
            <div class="nav-item dropdown user-menu mx-0">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary topbar-action-button topbar-dropdown-toggle topbar-user-button"
                        data-bs-toggle="dropdown"
                        aria-label="Benutzermenü öffnen"
                        title="Benutzermenü">
                    <i v-if="!user.image" class="mdi mdi-account"></i>
                    <img v-else
                         class="rounded-circle topbar-user-avatar"
                         :src="user.image.replace('attachments/', '/image/')"
                         width="22"
                         height="22"
                         :alt="user.name" />
                </button>
                <ul class="dropdown-menu dropdown-menu-end profile-menu">
                    <li class="profile-menu-header">
                        <div class="profile-menu-avatar">
                            <i v-if="!user.image" class="mdi mdi-account"></i>
                            <img v-else
                                 class="rounded-circle"
                                 :src="user.image.replace('attachments/', '/image/')"
                                 width="56"
                                 height="56"
                                 :alt="user.name" />
                        </div>
                        <div class="profile-menu-copy">
                            <div class="profile-menu-name">{{ user.name }}</div>
                            <div v-if="user.email" class="profile-menu-email">{{ user.email }}</div>
                        </div>
                    </li>
                    <li>
                        <inertia-link :href="route('user.profile')" class="dropdown-item profile-menu-item">
                            <span class="profile-menu-item-icon mdi mdi-cog-outline"></span>
                            <span>
                                <strong>Einstellungen</strong><br>
                                <small>Profil, Sicherheit und persönliche Optionen</small>
                            </span>
                        </inertia-link>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <inertia-link :href="route('logout')" class="dropdown-item profile-menu-item profile-menu-item-danger">
                            <span class="profile-menu-item-icon mdi mdi-logout"></span>
                            <span>
                                <strong>Abmelden</strong><br>
                                <small>Sitzung auf diesem Gerät beenden</small>
                            </span>
                        </inertia-link>
                    </li>
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

.topbar-action-button {
    width: 2.5rem;
    height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border-radius: 0;
    color: var(--bs-secondary-color);
}

.topbar-action-button:hover,
.topbar-action-button:focus,
.topbar-action-button.show {
    color: var(--bs-primary);
    background-color: var(--bs-primary-bg-subtle);
    border-color: var(--bs-primary-border-subtle);
}

.topbar-dropdown-toggle::after {
    display: none;
}

.topbar-action-button .mdi {
    font-size: 1.2rem;
    line-height: 1;
}

.topbar-user-avatar {
    object-fit: cover;
}

.topbar-settings-menu {
    width: min(24rem, calc(100vw - 1rem));
}

.profile-menu {
    width: min(22rem, calc(100vw - 2rem));
    padding: .5rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0;
    box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, .12);
}

.profile-menu-header {
    display: flex;
    align-items: center;
    gap: .875rem;
    padding: .75rem;
    margin-bottom: .25rem;
    background: linear-gradient(135deg, var(--bs-primary-bg-subtle), rgba(var(--bs-white-rgb), .95));
    border-radius: 0;
}

.profile-menu-avatar {
    width: 3.5rem;
    height: 3.5rem;
    flex: 0 0 3.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), .1);
    color: var(--bs-primary);
}

.profile-menu-avatar .mdi {
    font-size: 1.8rem;
}

.profile-menu-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-menu-copy {
    min-width: 0;
}

.profile-menu-name {
    font-weight: 600;
    color: var(--bs-emphasis-color);
}

.profile-menu-email {
    color: var(--bs-secondary-color);
    font-size: .875rem;
    overflow-wrap: anywhere;
}

.profile-menu-item {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: .75rem;
    border-radius: 0;
    white-space: normal;
}

.profile-menu-item:hover,
.profile-menu-item:focus {
    background-color: var(--bs-primary-bg-subtle);
    color: inherit;
}

.profile-menu-item-icon {
    margin-top: .125rem;
    font-size: 1.125rem;
    color: var(--bs-primary);
}

.profile-menu-item small {
    color: var(--bs-secondary-color);
}

.profile-menu-item-danger:hover,
.profile-menu-item-danger:focus {
    background-color: var(--bs-danger-bg-subtle);
}

.profile-menu-item-danger .profile-menu-item-icon,
.profile-menu-item-danger strong {
    color: var(--bs-danger);
}
</style>

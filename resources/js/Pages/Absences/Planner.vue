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
    <admin-layout :title="pageTitle" no-content-header>
        <template #navbar-left>
            <absence-nav :year="year" :month="month" :years="years" />
        </template>
        <template #navbar-right>
            <div v-if="plannerVisibilityUsers.length" class="planner-topbar-actions">
                <div class="btn-group" role="group">
                    <button
                        id="plannerVisibilityDropdown"
                        type="button"
                        class="btn btn-outline-secondary dropdown-toggle planner-visibility-toggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <span class="mdi mdi-account-multiple-outline"></span>
                        <span class="d-none d-md-inline"> Mitarbeitende </span>
                        <span class="badge rounded-pill bg-secondary ms-1">
                            {{ activeToggleableUsersCount }}/{{ plannerVisibilityUsers.length }}
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end planner-visibility-dropdown" aria-labelledby="plannerVisibilityDropdown">
                        <div class="dropdown-header">Mitarbeitende ein- oder ausblenden</div>
                        <button
                            v-for="user in plannerVisibilityUsers"
                            :key="'toggle-' + user.id"
                            type="button"
                            class="dropdown-item planner-visibility-item"
                            @click.prevent.stop="togglePinned(user)"
                        >
                            <span class="planner-visibility-name">{{ formatUserName(user) }}</span>
                            <span class="badge rounded-pill" :class="user.pinned ? 'bg-success text-dark' : 'bg-secondary'">
                                {{ user.pinned ? 'An' : 'Aus' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div v-if="loadingUsers" class="alert alert-info">
            <span class="mdi mdi-spin mdi-loading"></span>
            Lade anzuzeigende Benutzer...
        </div>
        <div v-if="loadingDates" class="alert alert-info">
            <span class="mdi mdi-spin mdi-loading"></span>
            Lade Einträge für {{ loadingDates }} Benutzer...
        </div>

        <card class="planner-card">
            <card-body class="p-0">
                <div class="table-responsive tbl-absences">
                    <table class="table table-bordered table-hover table-sm mb-0 absence-planner-table">
                        <thead>
                        <tr>
                            <th class="planner-sticky-column planner-name-header">Person</th>
                            <th
                                v-for="day in calendarDays"
                                :key="day.key"
                                class="cal-cell text-center planner-day-header"
                                :class="headerDayClass(day)"
                            >
                                <div class="fw-semibold">{{ day.weekdayShort }}</div>
                                <div>{{ day.dayOfMonth }}</div>
                            </th>
                        </tr>
                        </thead>
                        <tbody v-for="(category, categoryIndex) in sections" :key="category">
                        <tr
                            v-if="categoryIndex > 0"
                            class="category-header"
                            @click="toggleRow(category)"
                        >
                            <th :colspan="sectionColumnCount" class="planner-sticky-column">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span>
                                        <span :class="openSections[category] ? 'mdi mdi-chevron-down' : 'mdi mdi-chevron-right'" />
                                        <span class="mdi mdi-account-multiple"></span>
                                        {{ category }}
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        {{ users[category]?.length || 0 }}
                                    </span>
                                </div>
                            </th>
                        </tr>
                        <template v-for="user in users[category]" :key="user.id">
                            <tr v-if="openSections[category] && userDays[user.id]" :key="userRowKey(category, user)">
                                <th class="user-name planner-sticky-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="planner-user-label">{{ formatUserName(user) }}</div>
                                        </div>
                                        <div v-if="user.canEdit" class="btn-group btn-group-sm planner-user-actions" role="group">
                                            <inertia-link
                                                v-if="user.canEdit"
                                                class="btn btn-success me-1"
                                                title="Neuen Urlaubseintrag hinzufügen"
                                                :href="route('absence.create', { year: year, month: month, user: user.id })"
                                            >
                                                <span class="mdi mdi-briefcase-plus"></span>
                                            </inertia-link>
                                            <inertia-link
                                                v-if="user.canEdit && pools.length > 0"
                                                class="btn btn-primary"
                                                title="Poolmaster:in werden"
                                                :href="route('admin.poolmasters.create', { user: user.id, year, month })"
                                            >
                                                <span class="mdi mdi-account-tie"></span>
                                            </inertia-link>
                                        </div>
                                    </div>
                                </th>
                                <template v-for="day in calendarDays" :key="day.key + '-' + user.id">
                                    <td
                                        v-if="getUserDay(user, day)?.show"
                                        class="cal-cell"
                                        :class="cellClass(user, day)"
                                        :title="cellTitle(user, day)"
                                        :colspan="colspan(user, day)"
                                        @click="edit(user, day, getUserDay(user, day)?.absence)"
                                    >
                                        <div
                                            v-if="getUserDay(user, day)?.duration"
                                            class="absence"
                                            :title="cellTitle(user, day)"
                                            :class="{ editable: canEditCell(user, getUserDay(user, day)?.absence) }"
                                        >
                                            <strong>{{ getUserDay(user, day)?.absence?.user?.name }}</strong>
                                            <span v-if="showAbsenceReason(user, day)">
                                                ({{ getUserDay(user, day)?.absence?.reason }})
                                            </span>
                                            <br />
                                            <small v-if="getUserDay(user, day)?.absence?.replacementText">
                                                V: {{ getUserDay(user, day)?.absence?.replacementText }}
                                            </small>
                                        </div>
                                        <div
                                            v-else
                                            class="cell-hitarea"
                                            :aria-label="cellTitle(user, day)"
                                            :title="cellTitle(user, day)"
                                        ></div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </card-body>
        </card>
    </admin-layout>
</template>

<script>
import AbsenceNav from "./AbsenceNav";
import Card from "../../components/Ui/cards/card";
import CardBody from "../../components/Ui/cards/cardBody";

export default {
    name: "Planner",
    components: {AbsenceNav, Card, CardBody},
    props: ['start', 'end', 'year', 'month', 'months', 'years', 'now', 'holidays', 'days', 'sectionConfig', 'pinList', 'pools'],
    mounted() {
        this.loadPlanner();
    },
    data() {
        let mask = {
            'Eigenes Konto': true,
        };
        mask[this.$page.props.labels.pastor+'nen'] = true;
        mask['Mitarbeitende'] = true;

        return {
            users: {},
            userDays: {},
            loadingUsers: true,
            loadingDates: 0,
            sections: [],
            openSections: this.normalizeOpenSections(this.sectionConfig, mask),
            pinnedUsers: this.pinList || [],
            toggleableUsers: [],
            isPastor: this.$page.props.currentUser.data.isPastor,
        }
    },
    computed: {
        pageTitle() {
            return 'Urlaubsplaner ' + moment(this.start).locale('de').format('MMMM YYYY');
        },
        calendarDays() {
            return Object.values(this.days || {})
                .filter(day => day && typeof day === 'object' && day.date && day.day !== undefined)
                .reduce((days, day) => {
                    if (days.find(existingDay => existingDay.day === day.day)) return days;
                    days.push({
                        ...day,
                        key: `day-${day.day}`,
                        weekdayShort: moment(day.date).locale('de').format('dd'),
                        dayOfMonth: moment(day.date).locale('de').format('DD'),
                    });
                    return days;
                }, []);
        },
        sectionColumnCount() {
            return this.calendarDays.length + 1;
        },
        plannerVisibilityUsers() {
            return this.toggleableUsers.filter(Boolean);
        },
        activeToggleableUsersCount() {
            return this.plannerVisibilityUsers.filter(user => user.pinned).length;
        },
    },
    methods: {
        normalizeOpenSections(sectionConfig, defaults) {
            return {
                ...defaults,
                ...(sectionConfig || {}),
            };
        },
        async loadPlanner() {
            const response = await axios.get(route('planner.users'));
            const users = this.sortUsers(response.data);
            this.loadingUsers = false;
            this.users = users;

            response.data.forEach(user => {
                this.loadingDates++;
                axios.get(route('planner.days', {user: user.id, date: moment(this.start).format('YYYY-MM')}))
                    .then(userResponse => {
                        this.userDays[user.id] = userResponse.data;
                    })
                    .finally(() => {
                        this.loadingDates--;
                    });
            });
        },
        formatUserName(user) {
            if (user.first_name && user.last_name) {
                return `${user.last_name}, ${user.first_name}`;
            }

            return user.name;
        },
        userRowKey(category, user) {
            return `${category}-${user.id}-${this.openSections[category] ? 'open' : 'closed'}`;
        },
        addUserToMitarbeitende(user) {
            if (!this.users['Mitarbeitende']) this.users['Mitarbeitende'] = [];
            if (!this.users['Mitarbeitende'].find((existingUser) => existingUser.id === user.id)) {
                this.users['Mitarbeitende'].push(user);
                this.users['Mitarbeitende'].sort((a, b) => a.sortName < b.sortName ? -1 : 1);
            }
        },
        removeUserFromMitarbeitende(user) {
            if (!this.users['Mitarbeitende']) return;
            this.users['Mitarbeitende'] = this.users['Mitarbeitende'].filter((existingUser) => existingUser.id !== user.id);
        },
        persistPlannerSetting(key, value) {
            axios.post(route('setting.set', {
                user: this.$page.props.currentUser.data.id,
                key: key,
            }), {
                value: value,
            });
        },
        dayClass(user, day) {
            let prefix = user.canEdit ? 'editable ' : '';
            if (moment(day.date).isoWeekday() === 7) return prefix + 'sunday';
            if (day.holiday) return prefix + 'vacation';
            return prefix + 'day';
        },
        headerDayClass(day) {
            let classes = 'day';
            if (day.holiday) classes += ' vacation';
            if (moment(day.date).isoWeekday() === 7) classes += ' sunday-header';
            return classes;
        },
        canEditCell(user, absence) {
            return !!(user?.canEdit || absence?.canEdit);
        },
        canCreateAbsenceOnDay(user, day) {
            const userDay = this.getUserDay(user, day);
            return !!(user.canEdit && userDay && !userDay.duration && !userDay.busy);
        },
        isCellClickable(user, day) {
            const userDay = this.getUserDay(user, day);
            if (userDay?.duration) {
                return this.canEditCell(user, userDay.absence);
            }

            return this.canCreateAbsenceOnDay(user, day);
        },
        cellClass(user, day) {
            const userDay = this.getUserDay(user, day);
            if (userDay?.duration) {
                return this.absenceClass(user, day) + (this.isCellClickable(user, day) ? ' is-clickable' : ' is-static');
            }

            let classes = this.dayClass(user, day);
            if (moment(day.date).isoWeekday() === 7 && userDay?.busy) {
                classes += ' sunday-busy';
            } else if (day.holiday && userDay?.busy) {
                classes += ' vacation-busy';
            } else if (userDay?.busy) {
                classes += ' busy-day';
            }
            classes += this.isCellClickable(user, day) ? ' is-clickable' : ' is-static';

            return classes;
        },
        cellTitle(user, day) {
            const userDay = this.getUserDay(user, day);
            if (userDay?.duration) {
                return this.absenceTitle(user, userDay.absence);
            }

            if (userDay?.busy) {
                return 'An diesem Tag ist bereits ein Gottesdienst eingetragen.';
            }

            if (this.canCreateAbsenceOnDay(user, day)) {
                return 'Klicken, um einen neuen Abwesenheitseintrag anzulegen.';
            }

            if (!user.canEdit) {
                return 'Für diese Person dürfen Sie an diesem Tag keinen Abwesenheitseintrag anlegen.';
            }

            return 'An diesem Tag kann kein neuer Abwesenheitseintrag angelegt werden.';
        },
        showAbsenceReason(user, day) {
            const absence = this.getUserDay(user, day)?.absence;
            return !!(absence && (user.canEdit || absence.canEdit || absence.replacing));
        },
        edit(user, day, absence) {
            if (!this.canEditCell(user, absence)) return;

            if (absence) {
                if (absence.poolmaster_id) {
                    if (!absence.canEdit) return;
                    this.$inertia.visit(route('admin.poolmaster.edit', {modelId: absence.poolmaster_id}));
                    return;
                }

                this.$inertia.visit(route('absence.edit', {absence: absence.id}));
                return;
            }

            if (!this.canCreateAbsenceOnDay(user, day)) return;

            this.$inertia.visit(route('absence.create', {
                year: this.year,
                month: this.month,
                day: day.day,
                user: user.id
            }));
        },
        absenceClass(user, day) {
            const absence = this.userDays[user.id][day.day].absence;
            let absenceStatus = ' absence-status-' + absence.workflow_status;
            let editable = ' not-editable';
            if (user.canEdit || absence.canEdit) editable = ' editable';
            if (absence.sick_days && (absence.canEdit || user.canEdit)) editable = ' sick editable';
            if (absence.poolmaster) return 'poolmaster' + editable;
            if (absence.replacing) return 'replacing' + editable;
            return 'absent' + absenceStatus + editable;
        },
        absenceTitle(user, absence) {
            if ((!user.canEdit) && (!absence.canEdit) && (!absence.replacing)) {
                return absence.user.name + ' (' + moment(absence.from).format('DD.MM.YYYY') + ' - '
                    + moment(absence.to).format('DD.MM.YYYY') + ')';
            }

            let statusText = '';
            let replacementText = '';
            if (absence.workflow_status === 0) statusText = ' Status: Warte auf Überprüfung. ';
            if (absence.workflow_status === 1) statusText = ' Status: Warte auf Genehmigung. ';
            if (absence.workflow_status === 10) statusText = ' Status: Warte auf Genehmigung. ';
            if (!absence.poolmaster && absence.replacementText) {
                replacementText = 'V: ' + absence.replacementText;
            }

            return absence.reason + ' (' + moment(absence.from).format('DD.MM.YYYY') + ' - '
                + moment(absence.to).format('DD.MM.YYYY') + ') ' + replacementText + statusText + (user.canEdit ? ' --> Klicken, um zu bearbeiten' : '');
        },
        colspan(user, day) {
            if (undefined === this.userDays[user.id]) return this.calendarDays.length;
            return this.userDays[user.id][day.day].duration || 1;
        },
        sortUsers(users) {
            let sortedUsers = {};
            let usedIds = [];
            let allToggleableUsers = [];

            this.sections = [];
            this.toggleableUsers = [];

            users.forEach(user => {
                if (user.id === this.$page.props.currentUser.data.id) {
                    sortedUsers['Eigenes Konto'] = [];
                    this.sections.push('Eigenes Konto');
                    sortedUsers['Eigenes Konto'].push(user);
                    usedIds.push(user.id);
                }
            });

            if (this.$page.props.currentUser.data.isPastor) {
                sortedUsers[this.$page.props.labels.pastor+'nen'] = [];
                this.sections.push(this.$page.props.labels.pastor+'nen');
                users.forEach(user => {
                    if (usedIds.includes(user.id)) return;
                    if (user.isPastor) {
                        user.pinned = true;
                        sortedUsers[this.$page.props.labels.pastor+'nen'].push(user);
                        usedIds.push(user.id);
                    }
                });
                sortedUsers['Mitarbeitende'] = [];
                this.sections.push('Mitarbeitende');
                users.forEach(user => {
                    if (usedIds.includes(user.id)) return;
                    if (!user.show_absences_with_services) {
                        user.pinned = this.pinnedUsers.includes(String(user.id)) || this.pinnedUsers.includes(user.id);
                        allToggleableUsers.push(user);
                    }
                    if (user.show_absences_with_services || user.pinned) {
                        user.pinned = true;
                        sortedUsers['Mitarbeitende'].push(user);
                        usedIds.push(user.id);
                    }
                });
            } else {
                this.sections.push('Mitarbeitende');
                sortedUsers['Mitarbeitende'] = [];
            }

            users.forEach(user => {
                if (usedIds.includes(user.id)) return;
                user.pinned = true;
                sortedUsers['Mitarbeitende'].push(user);
            });

            this.toggleableUsers = allToggleableUsers.sort((a, b) => a.sortName < b.sortName ? -1 : 1);

            return sortedUsers;
        },
        toggleRow(category) {
            this.openSections[category] = !this.openSections[category];
            this.persistPlannerSetting('planner_open_sections', this.openSections);
        },
        togglePinned(user) {
            user.pinned = !user.pinned;
            if (user.pinned) {
                this.pinnedUsers = [...new Set([...this.pinnedUsers, user.id])];
                this.addUserToMitarbeitende(user);
            } else {
                this.removeUserFromMitarbeitende(user);
                this.pinnedUsers = this.pinnedUsers.filter((x) => String(x) !== String(user.id));
            }

            this.persistPlannerSetting('planner_pinned_users', this.pinnedUsers);
        },
        getUserDay(user, day) {
            if (!user || !day) return null;
            return this.userDays?.[user.id]?.[day.day] || null;
        }
    }
}
</script>

<style scoped lang="scss">
@use '../../../sass/theme' as theme;

.planner-card {
    border: 0;
    box-shadow: var(--bs-box-shadow-sm);
}

.planner-topbar-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.planner-visibility-dropdown {
    min-width: 18rem;
    max-height: min(70vh, 30rem);
    overflow-y: auto;
}

.planner-visibility-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.planner-visibility-name {
    white-space: normal;
}

.planner-visibility-item .badge.bg-success,
.planner-visibility-item .badge.bg-success.text-dark {
    color: var(--bs-dark) !important;
}

.tbl-absences {
    max-height: calc(100vh - 14rem);
}

.tbl-absences .table {
    min-width: max-content;
}

.tbl-absences .table th,
.tbl-absences .table td {
    padding: 0.2rem;
    vertical-align: middle;
}

.planner-sticky-column {
    position: sticky;
    left: 0;
    z-index: 2;
    background: var(--bs-white);
}

.tbl-absences thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background: var(--bs-light);
}

.tbl-absences thead .planner-sticky-column {
    z-index: 4;
    background: var(--bs-light);
}

.planner-name-header,
.tbl-absences .table th.user-name {
    min-width: 16rem;
    max-width: 16rem;
    padding: 0.5rem 0.75rem;
}

.planner-user-label {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.25;
}

.cell-hitarea {
    display: block;
    min-height: 1.5rem;
    width: 100%;
    height: 100%;
}

.planner-user-actions .btn {
    white-space: nowrap;
}

.tbl-absences .cal-cell {
    font-size: 0.72rem;
    width: 2.5rem;
    min-width: 2.5rem;
    max-width: 2.5rem;
    overflow: hidden;
}

.planner-day-header {
    font-weight: 500;
}

.tbl-absences thead .planner-day-header.sunday-header {
    color: var(--bs-danger) !important;
}

.category-header,
.category-header th {
    cursor: pointer;
}

.category-header th {
    background: tint-color(theme.theme-color("secondary"), 88%);
    font-weight: 600;
}

.absent {
    background-color: theme.elkw-color("orange");
}

.tbl-absences .cal-cell.absent {
    background-color: theme.elkw-color("orange");
    border-right-color: theme.elkw-color("orange");
}

.tbl-absences .cal-cell.absent.absence-status-0,
.tbl-absences .cal-cell.absent.absence-status-1,
.tbl-absences .cal-cell.absent.absence-status-10 {
    background-color: tint-color(theme.theme-color("warning"), 50%);
}

.replacing {
    background-color: theme.elkw-color("hellblau");
}

.poolmaster {
    background-color: theme.elkw-color("dunkelblau");
    color: white;
}

.tbl-absences tbody .cal-cell.sunday {
    background-color: rgba(var(--bs-danger-rgb), 0.12) !important;
    color: var(--bs-danger) !important;
}

.tbl-absences tbody .cal-cell.sunday.sunday-busy {
    background-color: rgba(var(--bs-danger-rgb), 0.22) !important;
}

.tbl-absences tbody .cal-cell.busy-day {
    background-color: rgba(var(--bs-primary-rgb), 0.10) !important;
    color: var(--bs-primary) !important;
}

.vacation {
    background-color: rgba(25, 135, 84, 0.10);
    color: var(--bs-success-text-emphasis, var(--bs-success));
}

.tbl-absences tbody .cal-cell.vacation.vacation-busy {
    background-color: rgba(25, 135, 84, 0.18) !important;
    color: var(--bs-success-text-emphasis, var(--bs-success)) !important;
}

.sick.editable {
    background-color: tint-color(theme.elkw-color("dunkelrot"), 70%);
}

.sick.replacing {
    background-color: tint-color(theme.elkw-color("hellblau"), 50%);
}

.editable:hover {
    background-color: theme.theme-color("primary");
    color: white !important;
}

.day.editable:hover,
.sunday.editable:hover,
.vacation.editable:hover {
    background-color: theme.theme-color("success");
}

.tbl-absences .cal-cell.is-clickable {
    cursor: pointer;
}

.tbl-absences .cal-cell.is-static {
    cursor: default;
}

@media (max-width: 991.98px) {
    .tbl-absences {
        max-height: none;
    }

    .planner-name-header,
    .tbl-absences .table th.user-name {
        min-width: 13rem;
        max-width: 13rem;
    }
}
</style>

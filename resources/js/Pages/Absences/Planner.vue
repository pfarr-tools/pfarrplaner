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
    <admin-layout :title="pageTitle" no-content-header no-padding no-outer-padding>
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

        <div class="planner-page">
        <card class="planner-card">
            <card-body class="p-0 planner-card-body">
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
                        <tbody v-if="loadingUsers && !sections.length">
                        <tr v-for="index in skeletonRowCount" :key="'planner-skeleton-' + index" class="planner-skeleton-row">
                            <th class="user-name planner-sticky-column">
                                <div class="planner-skeleton planner-skeleton-name"></div>
                            </th>
                            <td v-for="day in calendarDays" :key="'planner-skeleton-cell-' + index + '-' + day.key" class="cal-cell">
                                <div class="planner-skeleton planner-skeleton-cell"></div>
                            </td>
                        </tr>
                        </tbody>
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
                            <tr v-if="openSections[category] && userDays[user.id]" :key="userRowKey(category, user)" class="planner-row-compact">
                                <th class="user-name planner-sticky-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="planner-user-label">{{ formatUserName(user) }}</div>
                                        </div>
                                        <div v-if="user.canEdit" class="btn-group btn-group-sm planner-user-actions" role="group">
                                            <button
                                                type="button"
                                                class="btn btn-success me-1"
                                                title="Neue Abwesenheit anlegen"
                                                @click.prevent="openAbsenceCreateModal(user)"
                                            >
                                                <span class="mdi mdi-briefcase-plus"></span>
                                            </button>
                                            <button
                                                v-if="availablePools(user).length > 0"
                                                type="button"
                                                class="btn btn-primary"
                                                title="Poolmaster:in anlegen"
                                                @click.prevent="openPoolmasterCreateModal(user)"
                                            >
                                                <span class="mdi mdi-account-tie"></span>
                                            </button>
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
                            <tr v-else-if="openSections[category] && isUserDaysLoading(user)" :key="userRowKey(category, user) + '-loading'" class="planner-skeleton-row">
                                <th class="user-name planner-sticky-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="planner-user-label">{{ formatUserName(user) }}</div>
                                        </div>
                                        <div v-if="user.canEdit" class="btn-group btn-group-sm planner-user-actions" role="group">
                                            <span class="planner-skeleton planner-skeleton-button"></span>
                                            <span v-if="pools.length > 0" class="planner-skeleton planner-skeleton-button"></span>
                                        </div>
                                    </div>
                                </th>
                                <td v-for="day in calendarDays" :key="user.id + '-loading-' + day.key" class="cal-cell">
                                    <div class="planner-skeleton planner-skeleton-cell"></div>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </card-body>
        </card>
        </div>

        <modal
            v-if="activeAbsenceCreateUser"
            title="Neue Abwesenheit anlegen"
            close-button-label="Anlegen"
            cancel-button-label="Abbrechen"
            max-width="54rem"
            @close="createAbsenceForRange(activeAbsenceCreateUser)"
            @cancel="closeCreateModals"
        >
            <p class="mb-3">
                Neue Abwesenheit für <strong>{{ formatUserName(activeAbsenceCreateUser) }}</strong>
            </p>
            <date-range-input
                label="Zeitraum"
                inline
                :model-value="draftAbsenceRange(activeAbsenceCreateUser)"
                @update:model-value="setDraftAbsenceRange(activeAbsenceCreateUser, $event)"
            />
        </modal>

        <modal
            v-if="activePoolmasterCreateUser"
            title="Poolmaster:in anlegen"
            close-button-label="Anlegen"
            cancel-button-label="Abbrechen"
            max-width="54rem"
            @close="createPoolmasterForRange(activePoolmasterCreateUser)"
            @cancel="closeCreateModals"
        >
            <p class="mb-3">
                Neuen Poolmaster-Einsatz für <strong>{{ formatUserName(activePoolmasterCreateUser) }}</strong>
            </p>
            <label class="form-label">Pool</label>
            <select
                class="form-select mb-3"
                :value="selectedPoolId(activePoolmasterCreateUser)"
                @change="setSelectedPoolId(activePoolmasterCreateUser, $event.target.value)"
            >
                <option
                    v-for="pool in availablePools(activePoolmasterCreateUser)"
                    :key="pool.id"
                    :value="pool.id"
                >
                    {{ pool.name }}
                </option>
            </select>
            <date-range-input
                label="Zeitraum"
                inline
                :model-value="draftPoolmasterRange(activePoolmasterCreateUser)"
                @update:model-value="setDraftPoolmasterRange(activePoolmasterCreateUser, $event)"
            />
        </modal>
    </admin-layout>
</template>

<script>
import { DateTime } from 'luxon';
import AbsenceNav from "./AbsenceNav";
import Card from "../../components/Ui/cards/card";
import CardBody from "../../components/Ui/cards/cardBody";
import DateRangeInput from "../../components/Ui/elements/DateRangeInput.vue";
import Modal from "../../components/Ui/modals/Modal.vue";
import { serializePlannerDateToBerlinDateString, serializePlannerDateToUtc } from "../../helpers/plannerDates";

export default {
    name: "Planner",
    components: {AbsenceNav, Card, CardBody, DateRangeInput, Modal},
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
            loadingUserDays: {},
            sections: [],
            openSections: this.normalizeOpenSections(this.sectionConfig, mask),
            pinnedUsers: this.pinList || [],
            toggleableUsers: [],
            isPastor: this.$page.props.currentUser.data.isPastor,
            draftAbsenceRanges: {},
            draftPoolmasterRanges: {},
            selectedPoolIds: {},
            activeAbsenceCreateUserId: null,
            activePoolmasterCreateUserId: null,
        }
    },
    computed: {
        pageTitle() {
            return 'Urlaubsplaner ' + this.formatPlannerDate(this.start, 'LLLL yyyy');
        },
        calendarDays() {
            return Object.values(this.days || {})
                .filter(day => day && typeof day === 'object' && day.date && day.day !== undefined)
                .reduce((days, day) => {
                    if (days.find(existingDay => existingDay.day === day.day)) return days;
                    days.push({
                        ...day,
                        key: `day-${day.day}`,
                        weekdayShort: this.formatPlannerDate(day.date, 'ccc'),
                        dayOfMonth: this.formatPlannerDate(day.date, 'dd'),
                    });
                    return days;
                }, []);
        },
        sectionColumnCount() {
            return this.calendarDays.length + 1;
        },
        skeletonRowCount() {
            return 6;
        },
        plannerVisibilityUsers() {
            return this.toggleableUsers.filter(Boolean);
        },
        activeToggleableUsersCount() {
            return this.plannerVisibilityUsers.filter(user => user.pinned).length;
        },
        activeAbsenceCreateUser() {
            return this.findUserById(this.activeAbsenceCreateUserId);
        },
        activePoolmasterCreateUser() {
            return this.findUserById(this.activePoolmasterCreateUserId);
        },
    },
    methods: {
        plannerDateTime(value) {
            if (!value) return null;
            if (DateTime.isDateTime(value)) {
                return value.setZone('Europe/Berlin').setLocale('de');
            }
            if (typeof value === 'string') {
                const iso = DateTime.fromISO(value, { setZone: true });
                if (iso.isValid) {
                    return iso.setZone('Europe/Berlin').setLocale('de');
                }
            }

            const fallback = moment(value);
            if (!fallback.isValid()) return null;

            return DateTime.fromJSDate(fallback.toDate())
                .setZone('Europe/Berlin')
                .setLocale('de');
        },
        formatPlannerDate(value, format) {
            const plannerDate = this.plannerDateTime(value);
            return plannerDate ? plannerDate.toFormat(format) : '';
        },
        plannerIsoWeekday(value) {
            return this.plannerDateTime(value)?.weekday || null;
        },
        normalizeOpenSections(sectionConfig, defaults) {
            return {
                ...defaults,
                ...(sectionConfig || {}),
            };
        },
        defaultPlannerRange() {
            const firstDay = this.calendarDays[0]?.date ?? this.start;
            const fallback = this.plannerDateTime(firstDay);

            return fallback ? [fallback.toJSDate(), fallback.toJSDate()] : [];
        },
        availablePools(user) {
            return user?.pools ?? [];
        },
        findUserById(userId) {
            if (!userId) return null;

            return Object.values(this.users)
                .flat()
                .find(user => Number(user.id) === Number(userId)) || null;
        },
        closeCreateModals() {
            this.activeAbsenceCreateUserId = null;
            this.activePoolmasterCreateUserId = null;
        },
        openAbsenceCreateModal(user) {
            this.draftAbsenceRange(user);
            this.activePoolmasterCreateUserId = null;
            this.activeAbsenceCreateUserId = user.id;
        },
        openPoolmasterCreateModal(user) {
            this.draftPoolmasterRange(user);
            this.selectedPoolId(user);
            this.activeAbsenceCreateUserId = null;
            this.activePoolmasterCreateUserId = user.id;
        },
        draftAbsenceRange(user) {
            if (!this.draftAbsenceRanges[user.id]) {
                this.draftAbsenceRanges[user.id] = this.defaultPlannerRange();
            }

            return this.draftAbsenceRanges[user.id];
        },
        setDraftAbsenceRange(user, range) {
            this.draftAbsenceRanges[user.id] = range;
        },
        draftPoolmasterRange(user) {
            if (!this.draftPoolmasterRanges[user.id]) {
                this.draftPoolmasterRanges[user.id] = this.defaultPlannerRange();
            }

            return this.draftPoolmasterRanges[user.id];
        },
        setDraftPoolmasterRange(user, range) {
            this.draftPoolmasterRanges[user.id] = range;
        },
        selectedPoolId(user) {
            if (!this.selectedPoolIds[user.id]) {
                this.selectedPoolIds[user.id] = this.availablePools(user)[0]?.id ?? '';
            }

            return this.selectedPoolIds[user.id];
        },
        setSelectedPoolId(user, poolId) {
            this.selectedPoolIds[user.id] = Number(poolId);
        },
        createAbsencePayload(user, from, to) {
            return {
                user_id: user.id,
                reason: 'Urlaub',
                from: serializePlannerDateToUtc(from),
                to: serializePlannerDateToUtc(to, { endOfDay: true }),
            };
        },
        submitNewAbsence(user, from, to) {
            this.$inertia.post(route('absence.store'), this.createAbsencePayload(user, from, to));
        },
        createAbsenceForRange(user) {
            const range = this.draftAbsenceRange(user);
            if (!range?.[0] || !range?.[1]) return;

            this.closeCreateModals();
            this.submitNewAbsence(user, range[0], range[1]);
        },
        createPoolmasterForRange(user) {
            const range = this.draftPoolmasterRange(user);
            const poolId = this.selectedPoolId(user);
            if (!range?.[0] || !range?.[1] || !poolId) return;

            this.$inertia.post(route('admin.poolmasters.store'), {
                user_id: user.id,
                pool_id: poolId,
                start: serializePlannerDateToBerlinDateString(range[0]),
                end: serializePlannerDateToBerlinDateString(range[1]),
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.closeCreateModals();
                    this.reloadUserDays(user);
                },
            });
        },
        async loadPlanner() {
            const response = await axios.get(route('planner.users'));
            const users = this.sortUsers(response.data);
            this.users = users;
            this.loadingUsers = false;

            response.data.forEach(user => {
                this.reloadUserDays(user);
            });
        },
        reloadUserDays(user) {
            if (!user?.id) return;

            this.loadingUserDays[user.id] = true;
            axios.get(route('planner.days', {user: user.id, date: this.formatPlannerDate(this.start, 'yyyy-MM')}))
                .then(userResponse => {
                    this.userDays[user.id] = userResponse.data;
                })
                .finally(() => {
                    delete this.loadingUserDays[user.id];
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
            if (this.plannerIsoWeekday(day.date) === 7) return prefix + 'sunday';
            if (day.holiday) return prefix + 'vacation';
            return prefix + 'day';
        },
        headerDayClass(day) {
            let classes = 'day';
            if (day.holiday) classes += ' vacation';
            if (this.plannerIsoWeekday(day.date) === 7) classes += ' sunday-header';
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
            if (this.plannerIsoWeekday(day.date) === 7 && userDay?.busy) {
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

            this.submitNewAbsence(user, day.date, day.date);
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
                return absence.user.name + ' (' + this.formatPlannerDate(absence.from, 'dd.MM.yyyy') + ' - '
                    + this.formatPlannerDate(absence.to, 'dd.MM.yyyy') + ')';
            }

            let statusText = '';
            let replacementText = '';
            if (absence.workflow_status === 0) statusText = ' Status: Warte auf Überprüfung. ';
            if (absence.workflow_status === 1) statusText = ' Status: Warte auf Genehmigung. ';
            if (absence.workflow_status === 10) statusText = ' Status: Warte auf Genehmigung. ';
            if (!absence.poolmaster && absence.replacementText) {
                replacementText = 'V: ' + absence.replacementText;
            }

            return absence.reason + ' (' + this.formatPlannerDate(absence.from, 'dd.MM.yyyy') + ' - '
                + this.formatPlannerDate(absence.to, 'dd.MM.yyyy') + ') ' + replacementText + statusText + (user.canEdit ? ' --> Klicken, um zu bearbeiten' : '');
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
        },
        isUserDaysLoading(user) {
            return !!this.loadingUserDays[user.id];
        }
    }
}
</script>

<style scoped lang="scss">
@use '../../../sass/theme' as theme;

.planner-card {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
    border: 0;
    box-shadow: var(--bs-box-shadow-sm);
}

.planner-page {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
}

.planner-card-body {
    display: flex;
    flex: 1 1 auto;
    min-height: 0;
    min-width: 0;
}

.planner-skeleton-row .planner-sticky-column {
    background: var(--bs-white);
}

.planner-skeleton {
    background: linear-gradient(90deg, #edf1f4 25%, #f8f9fa 37%, #edf1f4 63%);
    background-size: 400% 100%;
    animation: planner-skeleton-shimmer 1.4s ease infinite;
    border-radius: 0.25rem;
}

.planner-skeleton-name {
    height: 1rem;
    width: 75%;
    margin: 0.15rem 0;
}

.planner-skeleton-cell {
    height: 1.1rem;
    width: 100%;
}

.planner-skeleton-button {
    display: inline-block;
    height: 1.45rem;
    width: 1.75rem;
    margin-left: 0.25rem;
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

@keyframes planner-skeleton-shimmer {
    0% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0 50%;
    }
}

.tbl-absences {
    flex: 1 1 auto;
    min-height: 0;
    min-width: 0;
    height: 100%;
    overflow-x: auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.absence-planner-table {
    min-width: max-content;
}

.absence-planner-table th,
.absence-planner-table td {
    padding: 0.2rem;
    vertical-align: middle;
}

.planner-sticky-column {
    position: sticky;
    left: 0;
    z-index: 2;
    background: var(--bs-white);
}

.absence-planner-table thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background: var(--bs-light);
}

.absence-planner-table thead .planner-sticky-column {
    z-index: 4;
    background: var(--bs-light);
}

.planner-name-header,
.absence-planner-table th.user-name {
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

.absence-planner-table .cal-cell {
    font-size: 0.72rem;
    width: 2.5rem;
    min-width: 2.5rem;
    max-width: 2.5rem;
    overflow: hidden;
}

.planner-row-compact .cal-cell {
    height: 2.6rem;
    max-height: 2.6rem;
}

.planner-row-compact .absence {
    display: block;
    max-height: 2.2rem;
    overflow: hidden;
    line-height: 1.05;
}

.planner-row-compact .absence strong,
.planner-row-compact .absence span,
.planner-row-compact .absence small {
    display: inline;
}

.planner-day-header {
    font-weight: 500;
}

.absence-planner-table thead .planner-day-header.sunday-header {
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

.absence-planner-table .cal-cell.absent {
    background-color: theme.elkw-color("orange");
    border-right-color: theme.elkw-color("orange");
}

.absence-planner-table .cal-cell.absent.absence-status-0,
.absence-planner-table .cal-cell.absent.absence-status-1,
.absence-planner-table .cal-cell.absent.absence-status-10 {
    background-color: tint-color(theme.theme-color("warning"), 50%);
}

.replacing {
    background-color: theme.elkw-color("hellblau");
}

.poolmaster {
    background-color: theme.elkw-color("dunkelblau");
    color: white;
}

.absence-planner-table tbody .cal-cell.sunday {
    background-color: rgba(var(--bs-danger-rgb), 0.12) !important;
    color: var(--bs-danger) !important;
}

.absence-planner-table tbody .cal-cell.sunday.sunday-busy {
    background-color: rgba(var(--bs-danger-rgb), 0.22) !important;
}

.absence-planner-table tbody .cal-cell.busy-day {
    background-color: rgba(var(--bs-primary-rgb), 0.10) !important;
    color: var(--bs-primary) !important;
}

.vacation {
    background-color: rgba(25, 135, 84, 0.10);
    color: var(--bs-success-text-emphasis, var(--bs-success));
}

.absence-planner-table tbody .cal-cell.vacation.vacation-busy {
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

.absence-planner-table .cal-cell.is-clickable {
    cursor: pointer;
}

.absence-planner-table .cal-cell.is-static {
    cursor: default;
}

@media (max-width: 991.98px) {
    .tbl-absences {
        max-height: none;
    }

    .planner-name-header,
    .absence-planner-table th.user-name {
        min-width: 13rem;
        max-width: 13rem;
    }
}
</style>

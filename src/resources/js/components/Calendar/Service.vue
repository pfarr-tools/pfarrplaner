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
    <div>
        <div v-if="loading" class="service-loading text-center py-3 fs-3 mb-1 text-muted"><span class="mdi mdi-spin mdi-loading"></span></div>
        <div v-else :key="$componentState"
             :class="{
        'service-entry': 1,
        'editable': myService.isEditable && (!foreign),
        'mine': myService.isMine,
        'bg-info': myService.isMine && (!isFuneral),
        'highlighted': 0,
        'possible-target': targetMode,
        'funeral': isFuneral,
        'bg-dark': isFuneral,
             'reloading': loading,
             'foreign': foreign,
             'hidden': myService.hidden}"
             :title="myService.isEditable ? clickTitle(myService) : null"
             @click="myService.isEditable ? edit(myService, $event) : null"
        >
            <div :class="{'service-time': 1,  'service-special-time text-danger': myService.isSpecialTime}">
                {{ myService.timeText }}
            </div>
            <span class="separator">|</span>
            <div :class="{'service-location': 1, 'service-special-location text-danger': myService.isSpecialLocation}">
                {{ foreign ? myService.locationTextWithCity : myService.locationText }}
            </div>
            <img v-if="(!settings.show_cc_details) && (myService.cc)" src="/img/cc.png" :title="ccTitle(myService)">
            <span v-if="myService.youtube_url">
            <a :href="myService.youtube_url" target="_blank" class="youtube-link" title="Zum Youtube-Video"><span
                class="mdi mdi-youtube"></span></a>
            <a v-if="myService.city.youtube_channel_url" :href="myService.liveDashboardUrl" target="_blank"
               class="youtube-livedashboard-link" title="Zum LiveDashboard"><span class="mdi mdi-video"></span></a>
        </span>
            <div v-if="myService.isAlternateProprium"
                 title="Für diesen Gottesdienst wurde ein vom normalen Kalender abweichendes Proprium festgelegt.">
                <div class="service-description">
                    <div :style="'background-color: '+(myService.liturgicalInfo.litColor || myService.liturgicalInfo['CSS-Farbe'])" class="liturgy-color"></div>
                    {{ myService.liturgicalInfo.title || myService.liturgicalInfo.Bezeichnung }}
                </div>
            </div>
            <controlled-access v-if="myService.controlled_access" :service="myService"/>
            <div
                v-if="(myService.titleText != 'Gottesdienst') && (myService.titleText != 'GD') && (!isFuneral)"
                class="service-description">{{ myService.titleText }}
            </div>

            <div class="service-description" v-if="isFuneral">
                <span class="mdi mdi-grave-stone"></span>
                {{ myService.funeralSummary }}
            </div>
            <div class="service-description" v-html="myService.descriptionText"></div>
            <div class="service-description" v-if="myService.internal_remarks">
                <span class="mdi mdi-eye-off" title="Anmerkung nur für den internen Gebrauch"></span>
                {{ myService.internal_remarks }}
            </div>

            <calendar-service-participants :participants="myService.pastors" :text="participantText('P')" :category="$page.props.labels.code_pastor"
                                           :predicant="myService.need_predicant"/>
            <calendar-service-participants :participants="myService.organists" :text="participantText('O')"
                                           :category="$page.props.labels.code_organist" :predicant="0"/>
            <calendar-service-participants :participants="myService.sacristans" :text="participantText('M')"
                                           :category="$page.props.labels.code_sacristan" :predicant="0"/>
            <calendar-service-participants v-for="(participants,ministry) in otherParticipantText"
                                           :key="ministry"
                                           :participants="[]" :text="participants" :category="ministry" :predicant="0"/>
            <div v-if="hasPermission('gd-kasualien-lesen') || hasPermission('gd-kasualien-nur-statistik')">
                <div class="service-description" v-if="baptismCount > 0">
                    <span class="mdi mdi-water"
                          :title="hasPermission('gd-kasualien-lesen') ? myService.baptismSummary : ''"></span>
                    {{ baptismCount }}
                </div>
            </div>
            <div v-if="settings.show_cc_details && (myService.cc)">
                <hr/>
                <img src="/img/cc.png" :title="ccTitle(myService)">
                Kinderkirche: {{ myService.cc_lesson }} ({{ myService.cc_staff }})
            </div>
            <div v-if="!targetMode">
                <div v-if="myService.isEditable " class="overlay">
                    <div class="buttons d-flex justify-content-center flex-wrap w-100">
                        <a href="#" class="btn btn-primary mb-1 me-1" role="button"
                           title="Gottesdienst bearbeiten" @click.prevent.stop="editFromButton(myService, 'service.edit', $event)">
                            <span class="mdi mdi-pencil"></span>
                        </a>
                        <a href="#" class="btn btn-light mb-1 me-1" role="button"
                           title="Liturgie bearbeiten" @click.prevent.stop="editFromButton(myService, 'liturgy.editor', $event)">
                            <span class="mdi mdi-view-list"></span>
                        </a>
                        <a href="#" class="btn btn-light mb-1 me-1" role="button"
                           title="Predigt bearbeiten" @click.prevent.stop="editFromButton(myService, 'service.sermon.editor', $event)">
                            <span class="mdi mdi-microphone"></span>
                        </a>
                        <a href="#" class="btn btn-info mb-1 me-1" role="button"
                           title="Mich für diesen Gottesdienst eintragen"
                           @click.prevent.stop="selfEntry">
                            <span class="mdi mdi-target-account"></span>
                        </a>
                    </div>
                </div>
                <div v-if="!myService.isEditable" class="overlay">
                    <div class="buttons d-flex justify-content-center flex-wrap w-100">
                        <a href="#" class="btn btn-light mb-1 me-1" role="button"
                           title="Liturgie ansehen" @click.prevent.stop="editFromButton(myService, 'liturgy.editor', $event)">
                            <span class="mdi mdi-view-list"></span>
                        </a>
                        <a href="#" class="btn btn-info mb-1 me-1" role="button"
                           title="Mich für diesen Gottesdienst eintragen"
                           @click.prevent.stop="selfEntry">
                            <span class="mdi mdi-target-account"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <modal v-if="selfEntryModalVisible" title="Mich für diesen Gottesdienst eintragen" close-button-label="Eintragen"
            @cancel="selfEntryModalVisible = false" @close="doSelfEntry" min-height="50vh" :key="ministryListUpdated">
            <div v-if="myService.titleText != 'Gottesdienst'">{{ myService.titleText}}</div>
            <div class="mb-2">{{ moment(myService.date).format('DD.MM.YYYY')}}, {{ myService.timeText }}, {{ myService.locationTextWithCity }}</div>
            <form-selectize :key="ministryListUpdated" :options="availableMinistries" label="Für folgenden Dienst eintragen" v-model="selfEntryMinistries"
                            @input="changeSelfEntryMinistries"
                            multiple />
        </modal>
    </div>
</template>
<script>


import ControlledAccess from "./Element/ControlledAccess";
import CalendarServiceParticipants from "./Service/Participants.vue";
import CalendarServiceBaptism from "./Service/Baptism.vue";
import CalendarServiceFuneral from "./Service/Funeral.vue";
import CalendarServiceWedding from "./Service/Wedding.vue";
import Modal from "../Ui/modals/Modal.vue";
import FormSelectize from "../Ui/forms/FormSelectize.vue";

export default {
    name: 'CalendarService',
    emits: ['deleted'],
    components: {
        FormSelectize,
        Modal,
        CalendarServiceWedding,
        CalendarServiceFuneral, CalendarServiceBaptism, CalendarServiceParticipants, ControlledAccess
    },
    props: ['serviceId', 'service', 'targetMode', 'target', 'city'],
    inject: {
        settings: {
            default: () => ({}),
        },
    },
    computed: {
        foreign() {
            if (this.loading) return false;
            if (!this.city) return false;
            if (!this.city.is_org) return (this.city.id != this.myService.city_id);
            return !this.city.childIds.includes(this.myService.city_id);
        },
        isFuneral() {
            return !!(this.myService?.funeral || (this.myService?.funerals || []).length > 0);
        },
        baptismCount() {
            return this.myService?.baptismCount ?? (this.myService?.baptisms || []).length ?? 0;
        },
        otherParticipantText() {
            const result = { ...(this.myService?.participantText || {}) };
            delete result.P;
            delete result.O;
            delete result.M;
            return result;
        },
    },
    created() {
        this.$bus.on('selfentry-ministries-changed', this.onSelfEntryMinistriesChanged);
    },
    beforeUnmount() {
        this.$bus.off('selfentry-ministries-changed', this.onSelfEntryMinistriesChanged);
    },
    data() {
        let availableMinistries = [
            {id: 'P', name: 'Pfarrer:in' },
            {id: 'O', name: 'Organist:in' },
            {id: 'M', name: 'Mesner:in' },
        ];
        return {
            myService: this.service ? this.normalizeService(this.service) : null,
            loading: !this.service,
            availableMinistries,
            selfEntryModalVisible: false,
            selfEntryMinistries: this.$page.props.settings.selfentry_ministries || [],
            ministryListUpdated: 0,
        }
    },
    mounted() {
        if (!this.service && this.serviceId) {
            this.loadData();
        } else {
            this.refreshAvailableMinistries();
        }
    },
    watch: {
        service: {
            deep: true,
            handler(newValue) {
                if (!newValue) return;
                this.myService = this.normalizeService(newValue);
                this.loading = false;
                this.refreshAvailableMinistries();
            },
        },
    },
    methods: {
        loadData() {
            this.loading = true;
            this.$api().get(route('api.calendar.service', { service: this.serviceId })).then(response => {
                this.myService = this.normalizeService(response.data.data);
                this.refreshAvailableMinistries();
                this.loading = false;
                this.$updateComponentState();
                this.$forceUpdate();
            })
        },
        ccTitle(service) {
            return 'Parallel Kinderkirche (' + service.cc_location + ') zum Thema "' + service.cc_lesson + '": ' + service.cc_staff;
        },
        clickTitle(service) {
            if (this.targetMode && service.isEditable) {
                let people = [];
                this.target.people.forEach(person => people.push(person.name));
                return 'Klicken für ' + this.target.ministry + ': ' + people.join(', ');
            }
            return '';
        },
        redirect(url) {
            window.location.href = url;
        },
        edit(service, clickEvent) {
            if (this.targetMode) {
                let peopleIds = [];
                this.target.people.forEach(person => peopleIds.push(person.id));
                this.loading = true;

                this.$api().post(route('api.service.assign', this.myService.id), {
                    ministry: this.target.ministry,
                    users: peopleIds,
                    exclusive: this.target.exclusive,
                }).then(response => {
                    this.myService = this.normalizeService(response.data.service);
                    this.refreshAvailableMinistries();
                    this.loading = false;
                });
                return;
                clickEvent.preventDefault();
                clickEvent.stopPropagation();
            }
        },
        editFromButton(service, myRoute, clickEvent) {
            if (clickEvent.ctrlKey) {
                window.open(route(myRoute, service.slug), '_blank');
            } else {
                this.$inertia.visit(route(myRoute, service.slug));
            }
        },
        selfEntry() {
            this.selfEntryModalVisible = true;
            this.$forceUpdate();
        },
        doSelfEntry() {
            console.log('self-entry', this.selfEntryMinistries);
            this.selfEntryModalVisible = false;
            this.loading = true;
            this.$api().post(route('api.service.assign', this.myService.id), {
                ministry: this.selfEntryMinistries,
                users: [this.$page.props.currentUser.data.id],
                exclusive: false,
            }).then(response => {
                this.myService = this.normalizeService(response.data.service);
                this.refreshAvailableMinistries();
                this.loading = false;
                this.$forceUpdate();
            });
            this.$forceUpdate();
        },
        changeSelfEntryMinistries() {
            this.$bus.emit('selfentry-ministries-changed', this.selfEntryMinistries);
            this.setUserSetting('selfentry_ministries', this.selfEntryMinistries);
        },
        onSelfEntryMinistriesChanged(e) {
            this.selfEntryMinistries = e;
        },
        participantText(category) {
            return this.myService?.participantText?.[category] || '';
        },
        normalizeService(service) {
            return {
                ...service,
                participantText: service.participantText || this.buildParticipantTextFromCollections(service),
                funeral: service.funeral ?? ((service.funerals || []).length > 0),
                funeralSummary: service.funeralSummary || '',
                baptismCount: service.baptismCount ?? ((service.baptisms || []).length || 0),
                baptismSummary: service.baptismSummary || service.baptismsText || '',
                isSpecialTime: service.isSpecialTime ?? this.detectSpecialTime(service),
                isSpecialLocation: service.isSpecialLocation ?? (service.location == null),
                liturgicalInfo: service.liturgicalInfo || {},
            };
        },
        buildParticipantTextFromCollections(service) {
            const lines = {};
            const knownCategories = {
                P: service.pastors || [],
                O: service.organists || [],
                M: service.sacristans || [],
            };

            Object.keys(knownCategories).forEach(category => {
                const text = knownCategories[category]
                    .map(person => [person.title, person.first_name, person.last_name].filter(Boolean).join(' ').trim() || person.name)
                    .join(' | ');
                if (text) lines[category] = text;
            });

            Object.entries(service.ministriesByCategory || {}).forEach(([category, participants]) => {
                const text = (participants || [])
                    .map(person => [person.title, person.first_name, person.last_name].filter(Boolean).join(' ').trim() || person.name)
                    .join(' | ');
                if (text) lines[category] = text;
            });

            return lines;
        },
        detectSpecialTime(service) {
            if (service.location == null) return true;
            if (service.location.default_time == null) return true;
            if (service.location.default_time === '') return true;
            return service.time != service.location.default_time.substr(0, 5);
        },
        refreshAvailableMinistries() {
            this.availableMinistries = [
                {id: 'P', name: 'Pfarrer:in' },
                {id: 'O', name: 'Organist:in' },
                {id: 'M', name: 'Mesner:in' },
            ];
            (this.myService?.city?.default_ministries || []).forEach(ministry => {
                if (ministry && ministry.trim()) {
                    this.availableMinistries.push({ id: ministry, name: ministry });
                }
            });
            this.ministryListUpdated++;
        }
    }
}

</script>

<style scoped>

.service-loading {
    background-color: lightgray;
     border-radius: 0;
    text-align: center;
}

.service-entry.reloading {
    border-color: lightgoldenrodyellow;
    background-color: transparent !important;
    color: #f5d403 !important;
    font-size: 3em !important;
}

.service-entry.editable.possible-target:not(.mine) {
    background-color: lightgoldenrodyellow;
}

.service-entry.editable.possible-target:hover {
    background-color: #f5d403;
    box-shadow: 0 0 0 .2rem lightgoldenrodyellow;
    border: 0;
}


.service-entry.editable.possible-target {
    cursor: crosshair;
}

.service-entry.foreign {
    background-color: #eee !important;
    color: #bbb !important;
}

.service-entry.foreign .service-location {
    color: orange;
    font-weight: bold;
}

.youtube-link {
    color: red;
}

.youtube-livedashboard-link {
    color: darkgray;
}

.liturgy-color {
    display: inline-block;
    border: solid 1px gray;
    min-width: 10px;
    min-height: 10px;
     border-radius: 0;
     border-radius: 0;
}

.liturgy-color.white {
    background-color: white;
    border-color: darkgray;
}

.liturgy-color.black {
    background-color: black;
}

.liturgy-color.green {
    background-color: darkgreen;
}

.liturgy-color.purple {
    background-color: rebeccapurple;
}

.overlay {
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    background-color: #7777;
    overflow: hidden;
    width: 100%;
    height: 0;
}

.service-entry {
    position: relative;
}

.service-entry:hover .overlay {
    bottom: 0;
    height: 100%;
}

.buttons {
    white-space: nowrap;
    color: white;
    font-size: 20px;
    position: absolute;
    overflow: hidden;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
}

</style>

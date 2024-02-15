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
        'bg-success': myService.isMine,
        'highlighted': 0,
        'possible-target': targetMode,
        'funeral': myService.funerals.length > 0,
        'bg-dark': myService.funerals.length > 0,
        'reloading': loading,
        'foreign': foreign,
        'hidden': myService.hidden}"
             :title="myService.isEditable ? clickTitle(myService) : null"
             @click="myService.isEditable ? edit(service, $event) : null"
        >
            <div :class="{'service-time': 1,  'service-special-time text-warning': isSpecialTime(myService)}">
                {{ myService.timeText }}
            </div>
            <span class="separator">|</span>
            <div :class="{'service-location': 1, 'service-special-location text-warning': isSpecialLocation(myService)}">
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
                    <div :style="'background-color: '+myService.liturgicalInfo.litColor" class="liturgy-color"></div>
                    {{ myService.liturgicalInfo.title }}
                </div>
            </div>
            <controlled-access v-if="myService.controlled_access" :service="service"/>
            <div
                v-if="(myService.titleText != 'Gottesdienst') && (myService.titleText != 'GD') && (myService.funerals.length == 0)"
                class="service-description">{{ myService.titleText }}
            </div>

            <div class="service-description" v-if="myService.funerals.length > 0">
                <span class="mdi mdi-grave-stone"></span>
                <calendar-service-funeral v-for="(funeral, index) in myService.funerals" :key="funeral.id"
                                          :funeral="funeral" trailer=", " :trail="index > 0"/>
            </div>
            <div class="service-description" v-html="myService.descriptionText"></div>
            <div class="service-description" v-if="myService.internal_remarks">
                <span class="mdi mdi-eye-off" title="Anmerkung nur für den internen Gebrauch"></span>
                {{ myService.internal_remarks }}
            </div>

            <calendar-service-participants :participants="myService.pastors" :category="$page.props.labels.code_pastor"
                                           :predicant="myService.need_predicant"/>
            <calendar-service-participants :participants="myService.organists"
                                           :category="$page.props.labels.code_organist" :predicant="0"/>
            <calendar-service-participants :participants="myService.sacristans"
                                           :category="$page.props.labels.code_sacristan" :predicant="0"/>
            <calendar-service-participants v-for="participants,ministry in myService.ministriesByCategory"
                                           :key="ministry"
                                           :participants="participants" :category="ministry" :predicant="0"/>
            <div v-if="hasPermission('gd-kasualien-lesen') || hasPermission('gd-kasualien-nur-statistik')">
                <div class="service-description" v-if="myService.baptisms.length > 0">
                    <span class="mdi mdi-water"
                          :title="hasPermission('gd-kasualien-lesen') ? myService.baptismsText : ''"></span>
                    {{ myService.baptisms.length }}
                </div>
            </div>
            <div v-if="settings.show_cc_details && (myService.cc)">
                <hr/>
                <img src="/img/cc.png" :title="ccTitle(myService)">
                Kinderkirche: {{ myService.cc_lesson }} ({{ myService.cc_staff }})
            </div>
            <div v-if="myService.isEditable" class="overlay">
                <div class="buttons">
                    <a href="#" class="btn btn-primary mb-1" role="button"
                       title="Gottesdienst bearbeiten" @click.prevent.stop="editFromButton(myService, 'myService.edit', $event)">
                        <span class="mdi mdi-pencil"></span>
                    </a>
                    <a href="#" class="btn btn-light mb-1" role="button"
                       title="Liturgie bearbeiten" @click.prevent.stop="editFromButton(myService, 'liturgy.editor', $event)">
                        <span class="mdi mdi-view-list"></span>
                    </a><br />
                    <a href="#" class="btn btn-light mb-1" role="button"
                       title="Predigt bearbeiten" @click.prevent.stop="editFromButton(myService, 'myService.sermon.editor', $event)">
                        <span class="mdi mdi-microphone"></span>
                    </a>
                    <a href="#" class="btn btn-danger mb-1" role="button"
                       title="Gottesdienst löschen" @click.prevent.stop="deleteService(myService, index)">
                        <span class="mdi mdi-delete"></span>
                    </a>

                </div>
            </div>

        </div>
    </div>
</template>
<script>


import ControlledAccess from "./Element/ControlledAccess";
import CalendarServiceParticipants from "./Service/Participants.vue";
import CalendarServiceBaptism from "./Service/Baptism.vue";
import CalendarServiceFuneral from "./Service/Funeral.vue";
import CalendarServiceWedding from "./Service/Wedding.vue";

export default {
    name: 'CalendarService',
    components: {
        CalendarServiceWedding,
        CalendarServiceFuneral, CalendarServiceBaptism, CalendarServiceParticipants, ControlledAccess
    },
    props: ['serviceId', 'targetMode', 'target', 'city'],
    inject: ['settings'],
    computed: {
        foreign() {
            if (this.loading) return false;
            return this.city ? (this.city.id != this.myService.city_id) : false;
        }
    },
    data() {
        /*
        if (Array.isArray(myService.ministriesByCategory)) myService.ministriesByCategory = {};
        if (!myService.ministriesByCategory) myService.ministriesByCategory = {};
        this.service.city.default_ministries.forEach(ministry => {
            if (!myService.ministriesByCategory[ministry]) myService.ministriesByCategory[ministry] = [];
        });
         */

        return {
            myService: null,
            loading: true,
        }
    },
    mounted() {
        this.loadData();
    },
    methods: {
        loadData() {
            this.loading = true;
            console.log('service, serviceId', this.serviceId, this.myService);
            this.$api().get(route('api.calendar.service', { service: this.serviceId })).then(response => {
                this.myService = response.data.data;
                this.loading = false;
                this.$updateComponentState();
                this.$forceUpdate();
            })
        },
        isSpecialTime(service) {
            if (null == service.location) return true;
            if (null == service.location.default_time) return true;
            if ('' == service.location.default_time) return true;
            return service.time != service.location.default_time.substr(0, 5);
        },
        isSpecialLocation(service) {
            return service.location == null;
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

                this.$api().post(route('api.service.assign', service.id), {
                    ministry: this.target.ministry,
                    users: peopleIds,
                    exclusive: this.target.exclusive,
                }).then(response => {
                    this.myService = response.data.service;
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
        deleteService(service, index) {
            if (confirm('Willst du diesen Gottesdienst wirklich komplett löschen?')) {
                this.$api().delete(route('api.service.destroy', {
                    service: service.slug,
                })).then(response => {
                    this.services = this.services.splice(index, 1);
                });
            }
        },


    }
}

</script>

<style scoped>

.service-loading {
    background-color: lightgray;
    border-radius: .25em;
    text-align: center;
}

.service-entry.reloading {
    border-color: lightgoldenrodyellow;
    background-color: transparent !important;
    color: #ffc107 !important;
    font-size: 3em !important;
}

.service-entry.editable.possible-target:not(.mine) {
    background-color: lightgoldenrodyellow;
}

.service-entry.editable.possible-target:hover {
    background-color: #ffc107;
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
    border-radius: 5px;
    border-radius: .5em;
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

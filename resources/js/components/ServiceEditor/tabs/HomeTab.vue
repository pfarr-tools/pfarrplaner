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
    <div class="home-tab">
        <icon-block icon="mdi mdi-calendar">
            <div class="row">
                <div class="mb-2" :class="myService.city.konfiapp_apikey ? 'col-md-4' : 'col-md-8'">
                    <form-selectize label="Typ der Veranstaltung" v-model="myService.event_class"
                                    :options="[{id: 'service', name: 'Gottesdienst'}, {id: 'event', name: 'Andere Veranstaltung'}]"/>
                </div>
                <div class="col-md-4 pt-md-4">
                    <form-check name="hidden" label="Diese Veranstaltung in öffentlichen Listen nicht anzeigen."
                                v-model="service.hidden"/>
                </div>
                <div v-if="myService.city.konfiapp_apikey" class="col-md-4">
                    <konfi-app-event-type-select name="konfiapp_event_type" label="Veranstaltungsart in der KonfiApp"
                                                 :city="myService.city" v-model="service.konfiapp_event_type"
                                                 :help="service.konfiapp_event_qr ? 'Ein QR-Code mit der ID '+service.konfiapp_event_qr+' wurde angelegt.' : 'Es wurde noch kein QR-Code angelegt.'"
                    />
                </div>
            </div>
            <div v-if="myService.event_class != 'service'">
                <hr/>
                <form-input
                    name="title" label="Titel der Veranstaltung"
                    v-model="service.title"
                    :required="true"
                    help="z.B. für öffentliche Listen"/>
            </div>
        </icon-block>
        <icon-block icon="mdi mdi-clock">
            <div class="row">
                <div class="col-md-4"  :key="'startTime_'+myService.is_allday+endTimeUpdated">
                    <form-date-picker :label="(myService.event_class == 'service')  ? 'Datum und Uhrzeit' : 'Beginn'"
                                      v-model="myService.date"
                                      v-if="!myService.is_allday"
                                      @input="adjustEndTime"
                                      :config="myDateTimePickerConfig" iso-date/>
                    <form-date-picker label="Beginn" v-model="myService.date"
                                      v-if="myService.is_allday"
                                      @input="adjustEndTime"
                                      :config="myDatePickerConfig" iso-date/>
                </div>
                <div class="col-md-4" v-if="(myService.event_class != 'service')"  :key="'endTime_'+myService.is_allday+endTimeUpdated">
                    <form-date-picker label="Ende" v-model="myService.end"
                                      v-if="!myService.is_allday"
                                      :config="myDateTimePickerConfig" iso-date/>
                    <form-date-picker label="Ende" v-model="myService.end"
                                      v-if="myService.is_allday"
                                      @input="adjustAllDay"
                                      :config="myDatePickerConfig" iso-date/>
                </div>
                <div v-if="myService.city.communiapp_token" class="col-md-4">
                    <form-group label="In der CommuniApp anzeigen ab"
                                help="Leer lassen für den Standard (8 Tage vor Beginn)">
                        <date-picker v-model="myService.communiapp_listing_start" :config="myDatePickerConfig"/>
                    </form-group>
                </div>
                <div class="col-md-4" v-if="myService.event_class == 'service'">
                    <proprium-select label="Zugehöriges Proprium" :liturgy-info="liturgyInfo"
                                     v-model="myService.liturgy_info_id"/>
                </div>
            </div>
            <form-check v-if="(myService.event_class != 'service')" label="Ganztägige Veranstaltung"
                        class="mb-md-2" @input="adjustAllDay"
                        v-model="myService.is_allday" />
        </icon-block>
        <icon-block icon="mdi mdi-map-marker">
            <div class="row">
                <div class="col-md-4">
                    <location-select name="location_id" label="Ort" :value="myLocation" v-if="!locationUpdating"
                                     :locations="locations" @set-location="setLocation" return-object
                                     :key="typeof myLocation == 'object' ? myLocation.id : myLocation   "
                    />
                </div>
                <div class="col-md-4">
                    <form-selectize name="controlled_access" :options="controlledAccessOptions"
                                    label="Zugangsbeschränkung"
                                    v-model="myService.controlled_access"/>
                </div>
                <div class="col-md-4">
                    <form-selectize name="related_cities[]" label="Auch in folgenden Kirchengemeinden anzeigen"
                                    v-model="service.related_cities"
                                    :options="cities" multiple/>
                </div>
            </div>
        </icon-block>
        <icon-block icon="mdi mdi-cross" v-if="myService.event_class == 'service'">
            <div class="row mb-2" >
                <div class="col-md-4" >
                    <form-check name="baptism" label="Dies ist ein Taufgottesdienst." v-model="service.baptism"/>
                </div>
                <div class="col-md-4" >
                    <form-check name="eucharist" label="Dies ist ein Abendmahlsgottesdienst." v-model="service.eucharist"/>
                </div>
            </div>
        </icon-block>
        <icon-block icon="mdi mdi-text">
            <div class="row">
                <div class="col-md-6" v-if="myService.event_class == 'service'">
                    <form-input name="title"
                                :label="myService.event_class == 'service' ? 'Abweichender Titel' : 'Titel der Veranstaltung'"
                                v-model="service.title"
                                :required="myService.event_class != 'service'"
                                help="z.B. für öffentliche Listen"/>
                </div>
                <div :class="myService.event_class == 'service' ? 'col-md-6' : 'col-md-12'">
                    <form-input name="description" label="Kurzbeschreibung" v-model="service.description"
                                help="Wird als Beschreibung in öffentlichen Listen angezeigt"/>
                </div>
            </div>
            <form-textarea name="internal_remarks" label="Interne Anmerkungen" v-model="service.internal_remarks"
                           help="Diese Anmerkungen werden nirgends veröffentlicht, sondern sind nur hier zu sehen."
                           pre-label="eye-slash"/>
            <div class="row">
                <div class="col-md-6">
                    <tag-select name="tags" label="Kennzeichnungen" v-model="service.tags"
                                help="Kennzeichnungen z.B. für den Gemeindebrief" :tags="tags"/>
                </div>
                <div class="col-md-6">
                    <service-group-select name="service_groups" label="Diese Veranstaltung gehört zu folgenden Gruppen"
                                          v-model="service.service_groups" help="Gruppen z.B. für den Gemeindebrief"
                                          :service-groups="serviceGroups"/>
                </div>
            </div>
            <div v-if="myService.event_class == 'service'">
                <hr/>
                <div v-if="hasAnnouncements" class="alert alert-warning"><b>Bitte beachte:</b> Diesem Gottesdienst wurde
                    eine Datei namens "Bekanntgaben" angehängt.
                    Diese überschreibt die automatisch erstellten Bekanntmachungen. Änderungen an diesem Feld werden daher
                    möglicherweise nicht berücksichtigt.
                </div>
                <form-textarea name="announcements" label="Zusätzliche Bekanntgaben" v-model="service.announcements"
                               help="Bekanntgaben, die über die automatisch erstellte Terminliste hinausgehen."/>
            </div>
        </icon-block>
    </div>
</template>

<script>
import FormInput from "../../Ui/forms/FormInput";
import LocationSelect from "../../Ui/elements/LocationSelect";
import DaySelect from "../../Ui/elements/DaySelect";
import PeopleSelect from "../../Ui/elements/PeopleSelect";
import FormCheck from "../../Ui/forms/FormCheck";
import MinistryRow from "../../Ui/elements/MinistryRow";
import FormTextarea from "../../Ui/forms/FormTextarea";
import FormSelectize from "../../Ui/forms/FormSelectize";
import KonfiAppEventTypeSelect from "../../Ui/elements/KonfiAppEventTypeSelect";
import FormGroup from "../../Ui/forms/FormGroup";
import DatePickerConfig from "../../Ui/config/DatePickerConfig.js";
import TagSelect from "../../Ui/elements/TagSelect";
import ServiceGroupSelect from "../../Ui/elements/ServiceGroupSelect";
import FormDatePicker from "../../Ui/forms/FormDatePicker";
import PropriumSelect from "../PropriumSelect.vue";
import FormRadioGroup from "../../Ui/forms/FormRadioGroup.vue";
import IconBlock from "../IconBlock.vue";

export default {
    name: "HomeTab",
    components: {
        IconBlock,
        FormRadioGroup,
        PropriumSelect,
        FormDatePicker,
        ServiceGroupSelect,
        TagSelect,
        FormGroup,
        KonfiAppEventTypeSelect,
        MinistryRow,
        PeopleSelect,
        DaySelect,
        LocationSelect,
        FormInput,
        FormCheck,
        FormSelectize,
        FormTextarea,
    },
    props: {
        service: Object,
        locations: Array,
        tags: Array,
        serviceGroups: Array,
        cities: Array,
        liturgyInfo: Array,
    },
    computed: {
        hasAnnouncements() {
            let found = false;
            this.service.attachments.forEach(attachment => {
                found = found || (attachment.title == 'Bekanntgaben');
            });
            return found;
        },
    },
    data() {
        let myService = this.service;
        myService.communiapp_listing_start = moment(this.service.communiapp_listing_start).format('DD.MM.YYYY');
        myService.event_class = myService.event_class || 'service';
        return {
            myService,
            myLocation: this.service.location || this.service.special_location,
            myDatePickerConfig: {
                format: 'DD.MM.YYYY',
                locale: 'de',
                showClear: true,
            },
            locationUpdating: false,
            controlledAccessOptions: [
                {id: 0, name: 'keine Zugangsbeschränkung'},
                {id: 1, name: '3G'},
                {id: 2, name: '2G'},
                {id: 3, name: '2G+'},
                {id: 4, name: 'Schnelltest für alle Besucher'},
                {id: 5, name: 'Schnelltest empfohlen'},
                {id: 6, name: 'Geschlossene Gruppe'},
            ],
            myDateTimePickerConfig: {
                locale: 'de',
                format: 'DD.MM.YYYY HH:mm',
                showClear: true,
                sideBySide: true,
            },
            endTimeUpdated: 0,
        }
    },
    methods: {
        setLocation(location) {
            this.locationUpdating = true;
            if (typeof location == 'object') {
                this.myLocation = location;
                this.myService.location_id = location.id;
                this.myService.location = location;
                this.myService.special_location = null;
            } else {
                this.myLocation = location;
                this.myService.special_location = location;
                this.myService.location_id = 0;
                this.myService.location = null;
            }
            this.locationUpdating = false;
        },
        setCommuniappListingStart(e) {
            this.service.communiapp_listing_start = e;
        },
        adjustEndTime(e) {
            if(this.myService.event_class === 'service') {
                this.adjustAllDay();
                return;
            }
            let startMoment = moment(e);
            if ((!this.myService.end) || (moment(this.myService.end) < startMoment)) {
                this.myService.end = startMoment.add(1, 'hours').toISOString();
                this.endTimeUpdated++;
            }
            this.adjustAllDay();
        },
        adjustAllDay() {
            if (!this.myService.is_allday) return;
            this.myService.date = moment(this.myService.date).startOf('day').toISOString();
            if (this.myService.end) this.myService.end = moment(this.myService.end).endOf('day').toISOString();
            this.endTimeUpdated++;
        }
    }
}
</script>

<style scoped>

</style>

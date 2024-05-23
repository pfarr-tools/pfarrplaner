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
    <div class="recurrence-tab">
        <form-check class="mb-2" label="Diese Veranstaltung wird regelmäßig wiederholt." v-model="recurring"/>
        <div v-if="recurring" :key="myService.rrule">
            <hr/>
            <div class="row">
                <div class="col-md-4">
                    <form-group class="form-inline" label="Wiederholen alle">
                        <div class="input-group">
                            <input type="number" class="form-control" aria-label="Intervall" min="1"
                                   v-model="recurrOptions.interval">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        @click="freqDropdown= !freqDropdown"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ freqText }}
                                </button>
                                <div class="dropdown-menu" :class="{show: freqDropdown}">
                                    <a class="dropdown-item" href="#"
                                       @click="recurrOptions.freq = 'DAILY'; freqDropdown = false;">Tage</a>
                                    <a class="dropdown-item" href="#"
                                       @click="recurrOptions.freq = 'WEEKLY'; freqDropdown = false;">Wochen</a>
                                    <a class="dropdown-item" href="#"
                                       @click="recurrOptions.freq = 'MONTHLY'; freqDropdown = false;">Monate</a>
                                    <a class="dropdown-item" href="#"
                                       @click="recurrOptions.freq = 'YEARLY'; freqDropdown = false;">Jahre</a>
                                </div>
                            </div>
                        </div>
                    </form-group>
                </div>
                <div class="col-md-4" v-if="recurrOptions.freq == 'WEEKLY'">
                    <form-group label="An folgenden Wochentagen">
                        <div>
                        <span
                            v-for="(dayName, dayKey) in {MO: 'Mo', TU: 'Di', WE: 'Mi', TH: 'Do', FR: 'Fr', SA: 'Sa', SU: 'So'}">
                            <input type="checkbox" class="form-check-input"
                                   :checked="recurrOptions.byday.includes(dayKey)"
                                   @input="setWeekDayFromInput($event, dayKey)"/> {{ dayName }}
                        </span>
                        </div>
                    </form-group>
                </div>
                <div class="col-md-8" v-if="recurrOptions.freq == 'MONTHLY'">
                    <label>Wiederholen bis:</label>
                    <div class="row mb-2">
                        <div class="col-1">
                            <input type="radio" class="form-check-input" value="dayNo"
                                   v-model="recurrOptions.monthMode">
                            <label>am:</label>
                        </div>
                        <div class="col-3">
                            <input class="form-control" type="number" v-model="recurrOptions.bymonthday" :disabled="recurrOptions.monthMode != 'dayNo'"/>
                        </div>
                        <div class="col-1">&nbsp;Tag</div>
                    </div>
                    <div class="row">
                        <div class="col-1">
                            <input type="radio" class="form-check-input"value="rule"
                                   v-model="recurrOptions.monthMode">
                            <label>am:</label>
                        </div>
                        <div class="col-4">
                            <select class="form-control" v-model="recurrOptions.bysetpos"  :disabled="recurrOptions.monthMode != 'rule'">
                                <option value="1">ersten</option>
                                <option value="2">zweiten</option>
                                <option value="3">dritten</option>
                                <option value="4">vierten</option>
                                <option value="5">fünften</option>
                                <option value="-3">drittletzten</option>
                                <option value="-2">vorletzten</option>
                                <option value="-1">letzten</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <select class="form-control" v-model="recurrOptions.byday"  :disabled="recurrOptions.monthMode != 'rule'">
                                <option value="MO">Montag</option>
                                <option value="TU">Dienstag</option>
                                <option value="WE">Mittwoch</option>
                                <option value="TH">Donnerstag</option>
                                <option value="FR">Freitag</option>
                                <option value="SA">Samstag</option>
                                <option value="SU">Sonntag</option>
                                <option value="MO,TU,WE,TH,FR">Wochentag</option>
                                <option value="SA,SU">Wochenendtag</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-7">
                    <label>Wiederholen bis:</label>
                    <div class="row">
                        <div class="col-6">
                            <input type="radio" class="form-check-input" name="repeat_until" value="always"
                                   v-model="recurrOptions.repeatMode">
                            <label>Ohne Ende</label>
                        </div>
                        <div class="col-6"></div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <input type="radio" class="form-check-input" name="repeat_until" value="until"
                                   v-model="recurrOptions.repeatMode">
                            <label>Datum</label>
                        </div>
                        <div class="col-6">
                            <form-date-picker v-model="recurrOptions.until" iso-date
                                              :disabled="recurrOptions.repeatMode != 'until'"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <input type="radio" class="form-check-input" name="repeat_until" value="count"
                                   v-model="recurrOptions.repeatMode">
                            <label>Anzahl Termine</label>
                        </div>
                        <div class="col-6">
                            <form-input type="number" v-model="recurrOptions.count"
                                        :disabled="recurrOptions.repeatMode != 'count'"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FormGroup from "../../Ui/forms/FormGroup.vue";
import FormCheck from "../../Ui/forms/FormCheck.vue";
import FormRadioGroup from "../../Ui/forms/FormRadioGroup.vue";
import FormDatePicker from "../../Ui/forms/FormDatePicker.vue";
import FormInput from "../../Ui/forms/FormInput.vue";

export default {
    name: "RecurrenceTab",
    components: {FormInput, FormDatePicker, FormRadioGroup, FormCheck, FormGroup},
    props: ['service'],
    computed: {
        freqText() {
            switch (this.recurrOptions.freq) {
                case 'DAILY':
                    return 'Tage';
                case 'WEEKLY':
                    return 'Wochen';
                case 'MONTHLY':
                    return 'Monate';
                case 'YEARLY':
                    return 'Jahre';
            }
        },
        RRule() {
            if (!this.recurring) return '';
            let rruleConditions = {
                'FREQ': this.recurrOptions.freq,
                'INTERVAL': this.recurrOptions.interval,
            };

            switch (this.recurrOptions.freq) {
                case 'WEEKLY':
                    if (this.recurrOptions.byday) rruleConditions.BYDAY = this.recurrOptions.byday;
                    break;
                case 'MONTHLY':
                    switch (this.recurrOptions.monthMode) {
                        case 'dayNo':
                            rruleConditions.BYMONTHDAY = this.recurrOptions.bymonthday;
                            break;
                        case 'rule':
                            rruleConditions.BYSETPOS = this.recurrOptions.bysetpos;
                            if (this.recurrOptions.byday) rruleConditions.BYDAY = this.recurrOptions.byday;
                            break;
                    }
                    break;
            }

            switch (this.recurrOptions.repeatMode) {
                case 'until':
                    delete rruleConditions['COUNT'];
                    rruleConditions.UNTIL = this.recurrOptions.until;
                    break;
                case 'count':
                    delete rruleConditions['UNTIL'];
                    rruleConditions.COUNT = this.recurrOptions.count;
                    break;
                case 'always':
                    delete rruleConditions['COUNT'];
                    delete rruleConditions['UNTIL'];
            }


            let rrule = [];
            for (const key in rruleConditions) {
                rrule.push(key + '=' + rruleConditions[key]);
            }

            this.myService.rrule = rrule.join(';');
            return 'RRULE:' + rrule.join(';');
        }
    },
    data() {
        let myService = this.service;
        myService.rrule = myService.rrule || '';
        let recurring = (myService.rrule != '');

        return {
            myService,
            recurring,
            recurrOptions: this.parse(myService),
            freqDropdown: false,
        }
    },
    watch: {
        recurring(newVal, oldVal) {
            if (newVal) {
                this.recurrOptions = this.setDefaults(this.myService);
            }
        },
        'recurrOptions.freq': function(newVal, oldVal) {
            switch (newVal) {
                case 'WEEKLY':
                case 'MONTHLY':
                    if (!this.recurrOptions.byday) this.recurrOptions.byday = ['SU','MO','TU','WE','TH','FR','SA'][moment(this.myService.date).day()];
                    break;
            }
        },
        RRule(newVal, oldVal) {
            this.myService.rrule = newVal;
        }
    },
    methods: {
        setDefaults(service) {
            return {
                freq: 'WEEKLY',
                until: moment(this.service.start).add(1, 'year').toISOString(),
                count: 12,
                interval: 1,
                byday: ['SU','MO','TU','WE','TH','FR','SA'][moment(service.date).day()],
                byyearday: null,
                bymonth: null,
                bymonthday: moment(service.date).date(),
                bysetpos: 1,
                repeatMode: 'always',
                monthMode: 'dayNo',
            }
        },
        parse(service) {
            if (service.rrule == '') return this.setDefaults(service);
            let parsed = {};
            let parts = service.rrule.replace('RRULE:', '').split(';');
            for (const key in parts) {
                let pSet = parts[key].split('=');
                parsed[pSet[0].toLowerCase()] = pSet[1];
            }
            if (parsed['interval']) parsed['repeatMode'] = 'count';
            if (parsed['until']) parsed['repeatMode'] = 'until';

            return {
                ...(this.setDefaults(service)),
                ...parsed,
            }
        },
        setWeekDayFromInput(e, wkDay) {
            this.recurrOptions.byday = this.recurrOptions.byday ? this.recurrOptions.byday.split(',').filter(e => e != wkDay).join(',') : '';
            if (e.target.checked) {
                let tmp = this.recurrOptions.byday.split(',').filter(e => e != '');
                tmp.push(wkDay);
                this.recurrOptions.byday = tmp.join(',');
            }
        }
    }
}
</script>

<style scoped>

</style>

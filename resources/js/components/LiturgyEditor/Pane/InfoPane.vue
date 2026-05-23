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

<template xmlns="http://www.w3.org/1999/html">
    <div class="liturgy-editor-info-pane">
        <div v-if="showable && liturgy['Bezeichnung']">
            <div v-if="myService.isAlternateProprium" class="alert alert-warning mb-1">
                In den Gottesdiensteinstellungen wurde ein vom normalen Kalender abweichendes Proprium
                festgelegt.
            </div>
            <div v-if="liturgy['Bezeichnung']">
                <div class="row">
                    <div class="col-12 col-md-10 fs-3">
                            <div v-if="liturgy['Bezeichnung']">
                                <b class="fw-bold me-1">{{ liturgy['Bezeichnung'] }}</b>
                                <span v-if="liturgy['Festkreis']" class="badge bg-info" :class="'bg-circle-'+liturgy['CSS-Farbe']">{{
                                        liturgy['Festkreis']
                                    }} ({{ romanize(liturgy['Lesejahr']) }}) </span>
                            </div>
                    </div>
                    <div class="col-12 col-md-2 text-md-end">
                        <div v-if="showMaterialsButton && Object.keys(myService.liturgicalInfo.Links).length > 0" class="dropdown">
                            <button type="button" id="dropdownLinksMenuButton" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false" title="Links zu Predigthilfen"
                                    class="btn btn-light btn-sm mt-1 dropdown-toggle"><span data-v-5d98b2c4=""
                                                                                            class="mdi mdi-text"></span>
                                Materialsammlung
                            </button>
                            <div aria-labelledby="dropdownLinksMenuButton" class="dropdown-menu">
                                <a v-if="myService.liturgicalInfo.DKJ" target="_blank"
                                   :href="myService.liturgicalInfo.DKJ.URL" class="dropdown-item">
                                    <div class="fw-bold">Das Kirchenjahr</div>
                                    <div>{{ myService.liturgicalInfo.Bezeichnung }}</div>
                                </a>
                                <a v-for="(link,linkTitle) in myService.liturgicalInfo.Links" target="_blank"
                                   :href="link" class="dropdown-item">
                                    <div class="fw-bold">{{ linkTitle.substring(1, linkTitle.indexOf(']')) }}</div>
                                    <div v-if="getLinkAuthor(linkTitle)" class="text-small fst-italic">
                                        {{ getLinkAuthor(linkTitle) }}
                                    </div>
                                    <div>{{ getLinkTitle(linkTitle) }}</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-2">
                        <bible-reference :perikope="liturgy.Wochenspruch" title="WSp"/>
                        <bible-reference :perikope="liturgy.Psalm" title="Ps"/>
                        <bible-reference :perikope="liturgy.Predigt" title="Pr"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <bible-reference :perikope="liturgy.Perikopen['Altes Testament']" title="AT"/>
                        <bible-reference :perikope="liturgy.Perikopen['Evangelium']" title="Ev"/>
                        <bible-reference :perikope="liturgy.Perikopen['Epistel']" title="Ep"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <bible-reference v-for="n in 3" :perikope="liturgy.Perikopen[n]"
                                         :key="'litTextsPerikope'+n"
                                         :title="romanize(n)"
                                         :style="{fontWeight: (n==liturgy['Lesejahr']) ? 'bold' : 'normal' }"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <bible-reference v-for="n in 3" :perikope="liturgy.Perikopen[(n+3)]"
                                         :key="'litTextsPerikope'+(n+3)"
                                         :title="romanize(n+3)"
                                         :style="{fontWeight: ((n+3)==liturgy['Lesejahr']) ? 'bold' : 'normal' }"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <bible-reference v-for="pKey in morePericopes" :perikope="liturgy.Perikopen[pKey]"
                                         :key="'litTextsPerikope'+pKey"
                                         :title="pKey" />
                    </div>
                    <div class="col-12 col-md-2">
                        <div v-if="liturgy['Lieder']" v-for="song in liturgy['Lieder']">
                            {{ song.Buch }} {{ song.Nummer }} {{ song.Titel }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="row">
                <div class="col-md-8">
                    Für {{ moment(myService.date).locale('de').format('dddd, DD.MM.YYYY') }} sind keine
                    liturgischen Informationen vorhanden.
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-start">
                        <proprium-select label="Proprium auswählen" @update:modelValue="setAlternativeProprium"
                                         v-model="myService.alt_proprium" />
                    </div>
                </div>
            </div>
        </div>
        <funeral-info-pane v-if="service.isEditable" v-for="funeral in myService.funerals"
                           :key="'funeral_info_'+funeral.id"
                           :funeral="funeral" :service="service"/>
    </div>
</template>

<script>
import BibleReference from "../Elements/BibleReference";
import FuneralInfoPane from "./FuneralInfoPane";
import FormDatePicker from "../../Ui/forms/FormDatePicker";
import PropriumSelect from "../../ServiceEditor/PropriumSelect.vue";

export default {
    name: "InfoPane",
    components: {PropriumSelect, FormDatePicker, FuneralInfoPane, BibleReference},
    data() {
        let pKeys = [];
        for (const key in this.service.liturgicalInfo.Perikopen || []) {
            if (isNaN(key) && (!(['Altes Testament', 'Evangelium', 'Epistel'].includes(key)))) pKeys.push(key);
        }

        let myService = this.service;
        if (undefined === myService.liturgicalInfo.Links) myService.liturgicalInfo.Links = [];

        return {
            myService,
            liturgy: this.service.liturgicalInfo,
            morePericopes: pKeys,
            originalAltDate: this.service.alt_liturgy_date,
        };
    },
    computed: {
        showable() {
            if ((!this.liturgy['title']) && (this.myService.funerals.length > 0)) return false;
            return true;
        },
    },
    props: {
        service: Object,
        showMaterialsButton: {
            type: Boolean,
            default: true,
        },
    },
    methods: {
        /**
         * @source http://blog.stevenlevithan.com/archives/javascript-roman-numeral-converter
         * @param num
         * @returns {string|number}
         */
        romanize(num) {
            if (isNaN(num))
                return NaN;
            var digits = String(+num).split(""),
                key = ["", "C", "CC", "CCC", "CD", "D", "DC", "DCC", "DCCC", "CM",
                    "", "X", "XX", "XXX", "XL", "L", "LX", "LXX", "LXXX", "XC",
                    "", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX"],
                roman = "",
                i = 3;
            while (i--)
                roman = (key[+digits.pop() + (i * 10)] || "") + roman;
            return Array(+digits.join("") + 1).join("M") + roman;
        },
        setAlternativeProprium(e) {
            let record = this.myService;
            delete record.participants;
            axios.patch(route('service.update', {service: this.myService.slug, format: 'json'}), record)
                .then(response => {
                    window.location.reload();
                });
        },
        getLinkTitle(link) {
            if (link.includes(']')) link = link.substring(link.indexOf(']') + 1).trim();
            if (link.includes('(')) link = link.substring(0, link.indexOf('(')).trim();
            return link;
        },
        getLinkAuthor(link) {
            if (!link.includes('(')) return '';
            link = (link.substring(link.indexOf('(') + 1, link.indexOf(')')));
            if (isNaN(link.substring(0, 1))) return link;
            return '';
        }
    }
}
</script>

<style scoped>
.liturgy-editor-info-pane {
    font-size: 0.8em;
    padding: 0.85rem 1.1rem;
    background: rgba(var(--bs-white-rgb), 0.95);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
    border-radius: 1rem;
}

.bg-circle-white {
    background-color: white !important;
    color: black !important;
    border: solid 1px lightgray;
}

.bg-circle-black {
    background-color: black !important;
    color: white !important;
}

.bg-circle-purple {
    background-color: purple !important;
    color: white !important;
}

.bg-circle-green {
    background-color: green !important;
    color: black !important;
}

.bg-circle-red {
    background-color: red !important;
    color: white !important;
}



.text-small {
    font-size: .8em;
}
</style>

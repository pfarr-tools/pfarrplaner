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
        <div v-if="showable">
        <div class="card" v-if="liturgy['title']">
            <div class="card-body">
                <div v-if="myService.isAlternateProprium" class="alert alert-warning mb-1">
                    In den Gottesdiensteinstellungen wurde ein vom normalen Kalender abweichendes Proprium festgelegt.
                </div>
                <div class="row" v-if="liturgy['title']">
                    <div class="col-12 col-md-2">
                        <div class="row">
                            <div class="col-9 col-md-12">
                                <div v-if="liturgy['title']">
                                    <b>{{ liturgy['title'] }}</b>
                                    <span v-if="liturgy['litColor']" class="litColor"
                                          :style="{ backgroundColor: liturgy['litColor'] }"
                                          :title="'Liturgische Farbe: '+liturgy['litColorName']"></span>
                                </div>
                                <div v-if="liturgy['feastCircleName']">
                            <span class="badge bg-info">{{
                                    liturgy['feastCircleName']
                                }} ({{ romanize(liturgy['perikope']) }}) </span>
                                </div>
                                <div v-if="liturgy['subjects']">
                        <span v-for="subject in liturgy['subjects']"
                              class="badge bg-light">{{ subject.subjectTitle }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="service.isEditable" class="dropdown">
                            <button type="button" id="dropdownLinksMenuButton" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false" title="Links zu Predigthilfen"
                                    class="btn btn-light btn-sm mt-1 dropdown-toggle"><span data-v-5d98b2c4=""
                                                                                class="mdi mdi-text"></span>
                                Materialsammlung
                            </button>
                            <div aria-labelledby="dropdownLinksMenuButton" class="dropdown-menu">
                                <a :href="'https://www.kirchenjahr-evangelisch.de/article.php#'+liturgy['dayId']" class="dropdown-item"
                                   target="_blank">
                                    <div class="fw-bold">Kirchenjahr Evangelisch</div>
                                    <div>{{ liturgy['title'] }}</div>
                                </a>
                                <a v-for="(link,linkTitle) in liturgy.links" target="_blank"
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
                    <div class="col-12 col-md-2">
                        <bible-reference :liturgy="myService.liturgicalInfo" liturgy-key="litTextsWeeklyQuote"
                                         title="WSp"/>
                        <bible-reference :liturgy="myService.liturgicalInfo" liturgy-key="litTextsWeeklyPsalm"
                                         title="Ps"/>
                        <bible-reference :liturgy="myService.liturgicalInfo" liturgy-key="currentPerikope" title="Pr"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <bible-reference v-for="n in 3" :liturgy="myService.liturgicalInfo"
                                         :liturgy-key="'litTextsPerikope'+n"
                                         :key="'litTextsPerikope'+n"
                                         :title="romanize(n)"
                                         :style="{fontWeight: (n==liturgy['perikope']) ? 'bold' : 'normal' }"/>
                    </div>
                    <div class="col-12 col-md-2">
                        <bible-reference v-for="n in 3" :liturgy="myService.liturgicalInfo"
                                         :liturgy-key="'litTextsPerikope'+(n+3)"
                                         :key="'litTextsPerikope'+(n+3)"
                                         :title="romanize(n+3)"
                                         :style="{fontWeight: ((n+3)==liturgy['perikope']) ? 'bold' : 'normal' }"/>
                    </div>
                    <div class="col-12 col-md-4">
                        <div v-if="liturgy['songs']" v-for="song in liturgy['songs']">
                            {{ song.number }} {{ song.title }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <card v-else>
            <card-body>
                <div class="row">
                    <div class="col-md-8">
                        Für {{ moment(myService.date).locale('de').format('dddd, DD.MM.YYYY') }} sind keine
                        liturgischen Informationen vorhanden.
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="text-start">
                            <proprium-select label="Proprium auswählen" @input="setAlternativeProprium"
                                             v-model="myService.liturgy_info_id" :liturgy-info="liturgyInfo"/>
                        </div>
                    </div>
                </div>
            </card-body>
        </card>
        </div>
        <funeral-info-pane v-if="service.isEditable" v-for="funeral in myService.funerals"
                           :key="'funeral_info_'+funeral.id"
                           :funeral="funeral" :service="service"/>
    </div>
</template>

<script>
import BibleReference from "../Elements/BibleReference";
import FuneralInfoPane from "./FuneralInfoPane";
import CardBody from "../../Ui/cards/cardBody";
import Card from "../../Ui/cards/card";
import FormDatePicker from "../../Ui/forms/FormDatePicker";
import PropriumSelect from "../../ServiceEditor/PropriumSelect.vue";

export default {
    name: "InfoPane",
    components: {PropriumSelect, FormDatePicker, Card, CardBody, FuneralInfoPane, BibleReference},
    data() {
        return {
            myService: this.service,
            liturgy: this.service.liturgicalInfo,
            originalAltDate: this.service.alt_liturgy_date,
        };
    },
    computed: {
        showable() {
            if ((!this.liturgy['title']) && (this.myService.funerals.length > 0)) return false;
            return true;
        }
    },
    props: ['service', 'liturgyInfo'],
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
}

.litColor {
    border: solid 1px gray;
    min-width: 10px;
    min-height: 10px;
    border-radius: 5px;
    display: inline-block;
}

.text-small {
    font-size: .8em;
}
</style>

<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarrplaner/pfarrplaner
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
    <modal title="Bibeltext einfügen" close-button-label="Einfügen"
           @cancel="$emit('input', '')"
           @close="$emit('input', insertBibleReference)">
        <form-bible-reference-input v-model="insertBibleReference" full-text
                                    :sources="textSources" :clipboard="false"/>
    </modal>
</template>

<script>
import Modal from "../../../Ui/modals/Modal.vue";
import FormBibleReferenceInput from "../../../Ui/forms/FormBibleReferenceInput.vue";
import {romanize} from "../../../../libraries/Romanize";
import RelativeDate from "../../../../libraries/RelativeDate";

export default {
    name: "InsertBibleTextDialog",
    props: ['service'],
    components: {FormBibleReferenceInput, Modal},
    data() {
        let textSources = {};
        if (undefined !== this.service.liturgicalInfo.title) {
            textSources['Perikope für ' + this.service.liturgicalInfo.title] = this.service.liturgicalInfo.currentPerikope;
            for (let i = 1; i <= 6; i++) {
                textSources[this.service.liturgicalInfo.title + ' ' + romanize(i)] = this.service.liturgicalInfo['litTextsPerikope' + i];
            }
            textSources[this.service.liturgicalInfo.title + ' Psalm'] = this.service.liturgicalInfo['litTextsWeeklyPsalm'];
            textSources[this.service.liturgicalInfo.title + ' Wochenspruch'] = this.service.liturgicalInfo['litTextsWeeklyQuote'];
        }

        this.service.baptisms.forEach(baptism => {
            if (baptism.text) textSources['Taufspruch ' + baptism.candidate_name] = baptism.text;
        });
        this.service.funerals.forEach(funeral => {
            if (funeral.text) textSources['Beerdigungstext ' + funeral.buried_name] = funeral.text;
            if (funeral.confirmation_text) textSources['Denkspruch ' + funeral.buried_name] = funeral.confirmation_text;
            if (funeral.wedding_text) textSources['Trauspruch ' + funeral.buried_name] = funeral.wedding_text;
        });
        this.service.weddings.forEach(wedding => {
            if (wedding.text) textSources['Trauspruch ' + wedding.spouse1_name + ' & ' + wedding.spouse2_name] = wedding.text;
        });


        return {
            insertBibleReference: '',
            textSources: textSources,
        }
    }
}
</script>

<style scoped>

</style>

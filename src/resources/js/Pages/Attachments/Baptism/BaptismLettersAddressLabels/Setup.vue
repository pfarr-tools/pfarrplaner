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

<script>
import FormInput from "../../../../components/Ui/forms/FormInput.vue";
import FormSelectize from "../../../../components/Ui/forms/FormSelectize.vue";
import FormSkipLabelsInput from "../../../../components/Ui/forms/FormSkipLabelsInput.vue";
import NavButton from "../../../../components/Ui/buttons/NavButton.vue";

export default {
    name: "Setup",
    props: ['baptism'],
    components: {NavButton, FormSkipLabelsInput, FormSelectize, FormInput},
    computed: {
        labels() {
            if (this.size == 37) return 24;
            return 21;
        }
    },
    data() {
        return {
            size: 37,
            years: 9,
            skipLabels: 0,
        }
    },
    methods: {
        download() {
            window.location.href = route('auto-attachment', {
                type: 'baptism', attachment: 'baptismLettersAddressLabels', attachable: this.baptism.id,
                size: this.size/10, years: this.years, skipLabels: this.skipLabels,
            })
        }
    }
}
</script>

<template>
    <admin-layout title="Adressetiketten drucken">
        <template v-slot:navbar-left>
            <nav-button type="primary" @click="download">Etiketten ausgeben</nav-button>
        </template>
        <form-selectize label="Etikettentyp" v-model="size" :options="[
            {id: 37, name: '70x37mm'},
            {id: 41, name: '70x41mm'},
        ]"/>
        <form-input label="Für wie viele Jahre sollen Etiketten gedruckt werden?" type="number" v-model="years"/>
        <form-skip-labels-input label="Wo soll mit dem Druck begonnen werden?"
                                v-model="skipLabels" :labels="labels" :length="years"
                                :key="'_labels_'+labels+'_'+'years'"/>
    </admin-layout>
</template>

<style scoped>

</style>

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
    <modal title="Liturgischen Text einfügen" close-button-label="Einfügen"
           @cancel="$emit('input', '')" min-height="50vh"
           @close="$emit('input', lists.texts.filter(item => item.id == selectedText)[0].text)">
        <div class="row">
            <div class="col-sm-6">
                <div v-if="lists.texts.length > 0">
                    <form-selectize label="Textbaustein" :options="lists.texts"
                                    title-key="title"
                                    v-model="selectedText" :key="lists.texts.length"/>
                </div>
            </div>
            <div class="col-sm-6">
                <nl2br tag="div" v-if="selectedText"
                       :text="lists.texts.filter(item => item.id == selectedText)[0].text"/>
            </div>
        </div>
    </modal>
</template>

<script>
import Nl2br from "vue-nl2br";
import Modal from "../../../Ui/modals/Modal.vue";
import NavButton from "../../../Ui/buttons/NavButton.vue";
import FormSelectize from "../../../Ui/forms/FormSelectize.vue";

export default {
    name: "InsertLiturgicTextDialog",
    components: {FormSelectize, NavButton, Modal, Nl2br},
    inject: ['lists'],
    data() {
        return {
            selectedText: '',
        }
    }
}
</script>

<style scoped>

</style>

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
    <div class="template-info-pane">
        <form-input label="Titel" v-model="myTemplate.title" autofocus/>
        <form-textarea label="Beschreibung" v-model="myTemplate.description"/>
        <form-input label="Quelle" v-model="myTemplate.special_location"/>
    </div>
</template>

<script>
import FormInput from "../../Ui/forms/FormInput.vue";
import FormTextarea from "../../Ui/forms/FormTextarea.vue";

export default {
    name: "InfoPane",
    components: {FormTextarea, FormInput},
    data() {
        return {
            myTemplate: this.value,
        };
    },
    props: ['value'],
    watch: {
        template: {
            deep: true,
            handler(newValue, oldValue ) {
                this.$emit('input', newValue);
            },
        }
    },
    methods: {
        editTemplateInfo() {
            this.editing = true;
        },
        saveTemplateInfo() {
            if (undefined == this.template.id) {
                this.$inertia.post(route('template.store'), this.template, {preserveState: false});
            } else {
                this.$inertia.patch(route('template.update', this.template.id), this.template, {preserveState: false});
            }
        }
    }
}
</script>

<style scoped>
.template-editor-info-pane {
    font-size: 0.8em;
}

.litColor {
    border: solid 1px gray;
    min-width: 10px;
    min-height: 10px;
     border-radius: 0;
    display: inline-block;
}
</style>

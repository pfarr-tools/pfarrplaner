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
    <admin-layout :title="myTag.name ? tag.name+' bearbeiten' : 'Neue Kennzeichnung' ">
        <template v-slot:navbar-left>
            <save-button @click="saveTag" />
            <nav-button class="ms-1" v-if="myTag.id"
                        @click="deleteTag" type="danger" title="Kennzeichnung löschen"
                        icon="mdi mdi-delete">Löschen</nav-button>
        </template>
        <form-input name="name" label="Bezeichnung" v-model="myTag.name" />
    </admin-layout>
</template>

<script>
import FormInput from "../../../components/Ui/forms/FormInput";
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import NavButton from "../../../components/Ui/buttons/NavButton";
export default {
    name: "Editor",
    components: {NavButton, SaveButton, FormInput},
    props: ['tag'],
    data() {
        let myTag = this.tag;
        if (undefined === myTag) myTag = {id: null, name: '', code: ''};

        return {
            myTag,
        }
    },
    methods: {
        saveTag() {
            if (this.myTag.id) {
                this.$inertia.patch(route('admin.tag.update', this.myTag.id), this.myTag);
            } else {
                this.$inertia.post(route('admin.tags.store'), this.myTag);
            }
        },
        deleteTag() {
            if (!confirm('Willst du diese Kennzeichnung wirklich komplett löschen?')) return;
            this.$inertia.delete(route('admin.tag.destroy', this.myTag.id));
        },
    }
}
</script>

<style scoped>

</style>

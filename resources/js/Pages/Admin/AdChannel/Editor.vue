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
    <admin-layout :title="myAdChannel.name ? myAdChannel.name+' bearbeiten' : 'Neuer Werbekanal' ">
        <template v-slot:navbar-left>
            <save-button @click="saveAdChannel" />
            <nav-button class="ms-1" v-if="myAdChannel.id"
                        @click="deleteAdChannel" type="danger" title="Werbekanal löschen"
                        icon="mdi mdi-delete">Löschen</nav-button>
        </template>
        <form-input name="name" label="Bezeichnung" v-model="myAdChannel.name" />
    </admin-layout>
</template>

<script>
import FormInput from "../../../components/Ui/forms/FormInput";
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import NavButton from "../../../components/Ui/buttons/NavButton";
export default {
    name: "Editor",
    components: {NavButton, SaveButton, FormInput},
    props: ['adChannel', 'city'],
    data() {
        return {
            myAdChannel: this.adChannel || { name: '', city_id: this.city ? this.city.id : null }
        }
    },
    methods: {
        saveAdChannel() {
            if (this.myAdChannel.id) {
                this.$inertia.patch(route('admin.adchannel.update', this.myAdChannel.id), this.myAdChannel);
            } else {
                this.$inertia.post(route('admin.adchannels.store'), this.myAdChannel);
            }
        },
        deleteAdChannel() {
            if (!confirm('Willst du diesen Werbekanal wirklich komplett löschen?')) return;
            this.$inertia.delete(route('admin.adchannel.destroy', this.myAdChannel.id));
        },
    }
}
</script>

<style scoped>

</style>

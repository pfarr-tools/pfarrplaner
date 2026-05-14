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
    <div class="profile-tab">
        <div class="row">
            <div class="col-md-8">
                <form-input name="first_name" label="Vorname" v-model="user.first_name" />
                <form-input name="last_name" label="Nachname" v-model="user.last_name" />
                <form-input name="email" label="E-Mailadresse" v-model="user.email" />
                <form-input name="office" label="Pfarramt/Büro" v-model="user.office" />
                <form-textarea name="address" label="Adresse" v-model="user.address" />
                <form-input name="phone" label="Telefon" v-model="user.phone" />
            </div>
            <div class="col-md-4">
                <form-image-attacher name="image" v-model="user.image" :no-pixabay="true"
                                     label="Benutzerbild" :allow-uncropped="false"
                                     :cropper-stencil="{aspectRatio: 1}"
                                     :attach-route="route('user.attach', {model: user.id})"
                                     :detach-route="route('user.detach', {model: user.id})"/>
            </div>
        </div>
    </div>
</template>

<script>
import FormInput from "../../Ui/forms/FormInput";
import FormTextarea from "../../Ui/forms/FormTextarea";
import FormImageAttacher from "../../Ui/forms/FormImageAttacher.vue";
export default {
    name: "ProfileTab",
    props: ['user'],
    components: {FormImageAttacher, FormTextarea, FormInput}
}
</script>

<style scoped>

</style>

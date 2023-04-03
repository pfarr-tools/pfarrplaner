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
    <admin-layout title="Als E-Mail versenden">
        <template v-slot:navbar-left>
            <nav-button type="primary" icon="mdi mdi-email-arrow-right-outline" @click="sendMail" title="Nachricht erstellen">Nachricht erstellen</nav-button>
        </template>
        <div class="alert alert-warning">
            Der Text deiner Nachricht ist zu lang, um automatisch an Outlook übergeben zu werden. Klicke auf
            "Nachricht erstellen", um den Text in die Zwischenanlage zu kopieren und eine leere Nachricht zu öffnen.
            Du kannst den Text anschließend mit Strg+V dort einfügen.
        </div>
        <form-textarea label="E-Mailtext" v-model="myBody" />
    </admin-layout>
</template>

<script>
import FormTextarea from "../../../components/Ui/forms/FormTextarea.vue";
import NavButton from "../../../components/Ui/buttons/NavButton.vue";

export default {
    name: "SongMailOverflow",
    props: ['body', 'subject', 'recipients', 'service'],
    data() {
        return {
            myBody: this.body,
        }
    },
    components: {NavButton, FormTextarea},
    methods: {
        sendMail() {
            const cb = navigator.clipboard;
            cb.writeText(this.myBody);
            window.location.href = 'mailto:'+this.recipients.join(',')+'?subject='+this.subject;
            this.$inertia.get(route('liturgy.editor', this.service.slug));
        }
    }
}
</script>

<style scoped>

</style>

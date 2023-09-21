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
    <admin-layout enable-control-sidebar="true" title="Agenden bearbeiten">
        <info-pane v-if="null != selectedAgenda" :agenda="selectedAgenda" />
        <div v-if="agendas.length > 0">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Agende</th>
                    <th>Beschreibung</th>
                    <th>Quelle</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="agenda in agendas" @click="openAgenda(agenda)" style="cursor: pointer" title="Klicken, um auszuwählen">
                    <td valign="top">{{ agenda.title }}</td>
                    <td valign="top">{{ agenda.description }}</td>
                    <td valign="top">{{ agenda.internal_remarks }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div v-else>Es sind noch keine Agenden angelegt.</div>
        <button class="btn btn-success" @click="createAgenda">Neue Agende anlegen</button>
    </admin-layout>
</template>

<script>
import moment from 'moment';

const InfoPane = () => import('../components/AgendaEditor/Pane/InfoPane');

export default {
    props: ['agendas'],
    components: {
        InfoPane,
    },
    data() {
        return {
            selectedAgenda: null,
            blockIndex: null,
            itemIndex: null,
            element: null,
        }
    },
    methods: {
        title(service) {
            return 'Liturgie für ' + moment(service.date).locale('de-DE').format('DD.MM.YYYY') + ', ' + service.timeText;
        },
        updateFocus(blockIndex, itemIndex, element) {
            this.blockIndex = blockIndex;
            this.itemIndex = itemIndex;
            this.element = element;
            this.showModal = true;
        },
        createAgenda() {
            axios.get(route('liturgy.agenda.create'))
                .then(response => {
                    return response.data;
                }).then(data => {
                this.selectedAgenda = data;
            });
        },
        openAgenda(agenda) {
            this.$inertia.get(route('liturgy.editor', agenda.slug));
        }
    }
}
</script>
<style scoped>
</style>

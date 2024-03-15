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
    <admin-layout enable-control-sidebar="true" :title="title(service)">
        <template slot="navbar-left">
            <span v-if="!templateMode">
                <inertia-link v-if="service.isEditable" class="btn btn-light" :href="route('service.edit', service.slug)"
                              title="Gottesdienst bearbeiten"><span class="mdi mdi-pencil"></span> Gottesdienst
                </inertia-link>&nbsp;
                <inertia-link v-if="service.isEditable" class="btn btn-light" :href="route('service.sermon.editor', service.slug)"
                              title="Predigt zu diesem Gottesdienst bearbeiten"><span class="mdi mdi-microphone"></span>
                    Predigt
                </inertia-link>&nbsp;
            </span>
            <span v-else>
                <save-button v-if="service.isEditable" @click="saveTemplate">Vorlage speichern</save-button>
            </span>
            <slot name="toolbar"/>
        </template>
        <template slot="control-sidebar" v-if="service.isEditable" >
            <form-check label="Zeitangaben runden" v-model="$page.props.settings.liturgy_times_rounded"
                        @input="setLiturgyTimesRounded"/>
            <form-input class="mt-2" label="Sprechgeschwindigkeit" v-model="$page.props.settings.wpm" @input="setWPM"
                        help="Wörter pro Minute"/>
            <button class="btn btn-sm btn-primary" @click.prevent.stop="reloadPage">Anwenden</button>
        </template>
        <template slot="after-flash">
            <info-pane v-if="!templateMode" :service="service" :liturgy-info="liturgyInfo" @info="infoWindow = true"/>
            <template-info-pane v-if="templateMode" v-model="service"/>
        </template>
        <liturgy-tree v-if="service.isEditable" :service="service" :sheets="templateMode ? {} : liturgySheets" :agenda-mode="templateMode"
                      :auto-focus-block="autoFocusBlock" :auto-focus-item="autoFocusItem"
                      :ministries="ministries" :markers="markers"
                      @update-focus="updateFocus"/>
        <liturgy-viewer v-else="service.isEditable" :service="service" :sheets="templateMode ? {} : liturgySheets" />
    </admin-layout>
</template>

<script>
import moment from 'moment';
import FormCheck from "../components/Ui/forms/FormCheck";
import FormInput from "../components/Ui/forms/FormInput";
import SaveButton from "../components/Ui/buttons/SaveButton.vue";

const InfoPane = () => import('../components/LiturgyEditor/Pane/InfoPane');
const TemplateInfoPane = () => import('../components/TemplateEditor/Pane/InfoPane');
const LiturgyTree = () => import('../components/LiturgyEditor/Pane/LiturgyTree');
const LiturgyViewer = () => import('../components/LiturgyEditor/Pane/LiturgyViewer');

export default {
    props: {
        service: Object,
        liturgySheets: Object,
        autoFocusBlock: {
            type: String,
            default: null,
        },
        autoFocusItem: {
            type: String,
            default: null,
        },
        ministries: {
            type: Object,
            default: [],
        },
        markers: {
            type: Object,
            default: null,
        },
        liturgyInfo: Array,
    },
    components: {
        SaveButton,
        FormInput,
        FormCheck,
        InfoPane,
        TemplateInfoPane,
        LiturgyTree,
        LiturgyViewer,
    },
    data() {
        if (undefined == this.$page.props.settings.liturgy_times_rounded) this.$page.props.settings.liturgy_times_rounded = false;
        if (undefined == this.$page.props.settings.liturgy_times_rounded) this.$page.props.settings.liturgy_times_rounded = 110;

        return {
            blockIndex: null,
            itemIndex: null,
            element: null,
            infoWindow: false,
            templateMode: moment(this.service.date).format('YYYYMMDD') == 19780305,
        }
    },
    methods: {
        title(service) {
            if (this.templateMode) return 'Vorlage bearbeiten';
            return 'Liturgie für ' + moment(service.date).locale('de-DE').format('DD.MM.YYYY') + ', ' + service.timeText;
        },
        updateFocus(blockIndex, itemIndex, element) {
            this.blockIndex = blockIndex;
            this.itemIndex = itemIndex;
            this.element = element;
            this.showModal = true;
        },
        setLiturgyTimesRounded() {
            this.$forceUpdate();
            this.$inertia.post(route('setting.set', {
                user: this.$page.props.currentUser.data.id,
                key: 'liturgy_times_rounded'
            }), {
                value: this.$page.props.settings.liturgy_times_rounded,
            });
            window.location.reload();
        },
        setWPM() {
            this.$forceUpdate();
            this.$inertia.post(route('setting.set', {user: this.$page.props.currentUser.data.id, key: 'wpm'}), {
                value: this.$page.props.settings.wpm,
            });
        },
        reloadPage() {
            window.location.reload();
        },
        saveTemplate() {
            this.$inertia.patch(route('template.update', this.service.id), this.service);
        },
        deleteTemplate() {},
    }
}
</script>
<style scoped>
</style>

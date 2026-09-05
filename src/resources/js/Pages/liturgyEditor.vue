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
    <admin-layout :enable-control-sidebar="true" :title="title(service)" no-content-header>
        <template #navbar-left>
            <button v-if="service.isEditable" class="btn btn-secondary me-2"
                    title="Ablaufelemente importieren" @click.prevent.stop="openImport">
                <span class="mdi mdi-file-import-outline me-lg-1"></span>
                <span class="d-none d-lg-inline">Importieren</span>
            </button>
            <liturgy-sheet-download-button class="me-2"
                                           :service="service"
                                           :sheets="templateMode ? {} : liturgySheets"
                                           :block-count="blockCount"
                                           :show-privileged="service.isEditable"/>
            <span v-if="!templateMode">
                <a v-if="service.isEditable" class="btn btn-secondary" title="Als Vorlage speichern" @click.prevent.stop="saveAsTemplate"><span class="mdi mdi-file-plus me-lg-1"></span><span class="d-none d-lg-inline">Als Vorlage speichern</span></a>&nbsp;
            </span>
            <span v-else>
                <save-button v-if="service.isEditable" @click="saveTemplate">Vorlage speichern</save-button>&nbsp;
                <a class="btn btn-danger" title="Vorlage löschen" @click.prevent.stop="deleteTemplate"><span class="mdi mdi-delete me-lg-1"></span><span class="d-none d-lg-inline">Löschen</span></a>
            </span>
            <slot name="toolbar"/>
        </template>
        <template #navbar-right>
            <div class="d-flex align-items-center">
                <liturgy-materials-button class="me-2" :service="service"/>
                <div class="btn-group calendar-mode-toggle" role="group" aria-label="Ansicht umschalten" v-if="service.isEditable">
                    <inertia-link class="btn btn-outline-secondary" :href="route('service.edit', service.slug)" title="Gottesdienst bearbeiten">
                        <span class="mdi mdi-pencil me-lg-1"></span>
                        <span class="d-none d-lg-inline">Bearbeiten</span>
                    </inertia-link>
                    <button class="btn btn-secondary" href="#">
                        <span class="mdi mdi-view-list me-lg-1"></span>
                        <span class="d-none d-lg-inline">Liturgie</span>
                    </button>
                    <inertia-link class="btn btn-outline-secondary" :href="route('service.sermon.editor', service.slug)" title="Liturgie anzeigen">
                        <span class="mdi mdi-microphone me-lg-1"></span>
                        <span class="d-none d-lg-inline">Predigt</span>
                    </inertia-link>
                </div>
            </div>
        </template>
        <template #control-sidebar v-if="service.isEditable" >
            <form-check label="Zeitangaben runden" v-model="$settings.liturgy_times_rounded"
                        @input="setLiturgyTimesRounded"/>
            <form-input class="mt-2" label="Sprechgeschwindigkeit" v-model="$settings.wpm" @input="setWPM"
                        help="Wörter pro Minute"/>
            <button class="btn btn-sm btn-primary" @click.prevent.stop="reloadPage">Anwenden</button>
        </template>
        <div class="liturgy-editor-page">
            <liturgy-workspace-header :service="service" :template-mode="templateMode"
                                      :editable="service.isEditable" :block-count="blockCount"/>
            <info-pane v-if="!templateMode" :service="service" :show-materials-button="false" @info="infoWindow = true"/>
            <template-info-pane v-if="templateMode" v-model="myService"/>
            <liturgy-tree ref="liturgyTree" v-if="service.isEditable" :service="service" :sheets="templateMode ? {} : liturgySheets" :agenda-mode="templateMode"
                      :auto-focus-block="autoFocusBlock" :auto-focus-item="autoFocusItem"
                      :ministries="ministries" :markers="markers"
                      @update-focus="updateFocus" @update-block-count="updateBlockCount"/>
            <liturgy-viewer v-else="service.isEditable" :service="service" :sheets="templateMode ? {} : liturgySheets" />
        </div>
    </admin-layout>
</template>

<script>
import dayjs from 'dayjs';
import FormCheck from "../components/Ui/forms/FormCheck";
import FormInput from "../components/Ui/forms/FormInput";
import SaveButton from "../components/Ui/buttons/SaveButton.vue";
import InfoPane from '../components/LiturgyEditor/Pane/InfoPane';
import TemplateInfoPane from '../components/TemplateEditor/Pane/InfoPane';
import LiturgyTree from '../components/LiturgyEditor/Pane/LiturgyTree';
import LiturgyViewer from '../components/LiturgyEditor/Pane/LiturgyViewer';
import LiturgyMaterialsButton from '../components/LiturgyEditor/Elements/LiturgyMaterialsButton.vue';
import LiturgySheetDownloadButton from '../components/LiturgyEditor/Elements/LiturgySheetDownloadButton.vue';
import LiturgyWorkspaceHeader from '../components/LiturgyEditor/Pane/LiturgyWorkspaceHeader.vue';

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
        LiturgyMaterialsButton,
        LiturgySheetDownloadButton,
        LiturgyWorkspaceHeader,
    },
    data() {
        if (undefined == this.$settings.liturgy_times_rounded) this.$settings.liturgy_times_rounded = false;
        if (undefined == this.$settings.wpm) this.$settings.wpm = 110;

        return {
            blockIndex: null,
            itemIndex: null,
            element: null,
            infoWindow: false,
            templateMode: this.service.isTemplate,
            myService: this.service,
            blockCount: (this.service.liturgy_blocks || []).length,
        }
    },
    methods: {
        title(service) {
            if (this.templateMode) return 'Vorlage bearbeiten';
            return 'Liturgie für ' + dayjs(service.date).locale('de').format('DD.MM.YYYY') + ', ' + service.timeText;
        },
        updateFocus(blockIndex, itemIndex, element) {
            this.blockIndex = blockIndex;
            this.itemIndex = itemIndex;
            this.element = element;
            this.showModal = true;
        },
        updateBlockCount(blockCount) {
            this.blockCount = blockCount;
        },
        setLiturgyTimesRounded() {
            this.$inertia.post(route('setting.set', {
                user: this.$page.props.currentUser.data.id,
                key: 'liturgy_times_rounded'
            }), {
                value: this.$settings.liturgy_times_rounded,
            });
            window.location.reload();
        },
        setWPM() {
            this.$inertia.post(route('setting.set', {user: this.$page.props.currentUser.data.id, key: 'wpm'}), {
                value: this.$settings.wpm,
            });
        },
        reloadPage() {
            window.location.reload();
        },
        openImport() {
            this.$refs.liturgyTree?.openImportModal();
        },
        saveTemplate() {
            this.$inertia.patch(route('template.update', this.myService.id), this.myService);
        },
        saveAsTemplate() {
            this.$inertia.post(route('template.saveAsTemplate', this.service.id), {}, { preserveState: false });
        },
        deleteTemplate() {
            if (!confirm('Willst du diese Vorlage wirklich unwiderruflich löschen?')) return;
            this.$inertia.delete(route('template.destroy', this.service.id));
        },
    }
}
</script>
<style scoped>
.liturgy-editor-page {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}
</style>

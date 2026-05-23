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
    <div v-if="hasDownload">
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="liturgySheetDownloadButton"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    title="Dokumente herunterladen">
                <span class="mdi mdi-download me-lg-1"></span>
                <span class="d-none d-lg-inline">Herunterladen</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="liturgySheetDownloadButton">
                <div v-for="sheet in downloadableSheets" :key="sheet.key">
                    <liturgy-sheet-link :service="service" :sheet="sheet"
                                        @open="openDialog(sheet.key)"/>
                </div>
            </div>
        </div>

        <template v-for="sheet in downloadableSheets" :key="'dlg' + sheet.key">
            <modal v-if="dialogs[sheet.key]" :title="sheet.title + ' herunterladen'"
                   @close="downloadConfiguredSheet(sheet)"
                   @cancel="dialogs[sheet.key] = false"
                   close-button-label="Herunterladen" cancel-button-label="Abbrechen">
                <component :is="sheet.configurationComponent" :service="service" :sheet="sheet"/>
            </modal>
        </template>
    </div>
</template>

<script>
import Modal from "../../Ui/modals/Modal";
import LiturgySheetLink from "./LiturgySheetLink";
import FullTextLiturgySheetConfiguration from "../LiturgySheets/FullTextLiturgySheetConfiguration";
import A4WordSpecificLiturgySheetConfiguration from "../LiturgySheets/A4WordSpecificLiturgySheetConfiguration";
import SongPPTLiturgySheetConfiguration from "../LiturgySheets/SongPPTLiturgySheetConfiguration";
import SongSheetLiturgySheetConfiguration from "../LiturgySheets/SongSheetLiturgySheetConfiguration";
import SBLiturgySheetConfiguration from "../LiturgySheets/SBLiturgySheetConfiguration.vue";

export default {
    name: "LiturgySheetDownloadButton",
    components: {
        Modal,
        LiturgySheetLink,
        FullTextLiturgySheetConfiguration,
        SongPPTLiturgySheetConfiguration,
        A4WordSpecificLiturgySheetConfiguration,
        SongSheetLiturgySheetConfiguration,
        SBLiturgySheetConfiguration,
    },
    props: {
        service: Object,
        sheets: Object,
        blockCount: {
            type: Number,
            default: 0,
        },
        showPrivileged: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            dialogs: this.createDialogs(this.sheets),
        };
    },
    computed: {
        downloadableSheets() {
            return Object.values(this.sheets).filter(sheet => this.showPrivileged || !sheet.privileged);
        },
        hasDownload() {
            return (this.blockCount > 0) && (this.downloadableSheets.length > 0);
        },
    },
    watch: {
        sheets(newSheets) {
            const newDialogs = this.createDialogs(newSheets);
            Object.keys(this.dialogs).forEach(key => {
                if (Object.prototype.hasOwnProperty.call(newDialogs, key)) newDialogs[key] = this.dialogs[key];
            });
            this.dialogs = newDialogs;
        },
    },
    methods: {
        createDialogs(sheets) {
            const dialogs = {};
            Object.values(sheets).forEach(sheet => {
                if (sheet.configurationComponent) dialogs[sheet.key] = false;
            });
            return dialogs;
        },
        openDialog(key) {
            this.$nextTick(() => {
                this.dialogs[key] = true;
            });
        },
        downloadConfiguredSheet(sheet) {
            document.getElementById('frm' + sheet.key).submit();
            this.dialogs[sheet.key] = false;
        },
    },
}
</script>

<style scoped>

</style>

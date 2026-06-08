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
    <div class="attachments-tab">
        <h3>Angehängte Dateien</h3>
        <div v-if="hasAutoAttachments || (service.attachments.length > 0)">
            <div v-if="myService.liturgy_blocks.length > 0">
                <div
                    v-for="sheet in downloadableLiturgySheets"
                    :key="sheet.key"
                    class="liturgy-sheet btn btn-light"
                    @click.prevent="sheet.configurationComponent ? openDialog(sheet.key) : downloadSheet(sheet)"
                >
                    <b><span :class="sheet.icon"></span> {{ sheet.title }}</b><br/>
                    <small>.{{ sheet.extension }}, Größe unbekannt</small>
                    <span class="float-right mdi mdi-download"></span>
                </div>
            </div>
            <div v-if="myService.konfiapp_event_qr">
                <div class="liturgy-sheet btn btn-light" @click.prevent="downloadQR">
                    <b><span class="mdi mdi-file-pdf-box"></span> QR-Code für Konfis</b><br/>
                    <small>.pdf, ca. 50 kB</small>
                    <span class="float-right mdi mdi-download"></span>
                </div>
            </div>
            <div v-if="!hasAnnouncements" class="liturgy-sheet btn btn-light" @click.prevent="downloadAnnouncements">
                <b><span class="mdi mdi-file-word-box"></span> Bekanntgaben</b><br/>
                <small>.docx, ca. 20 kB</small>
                <span class="float-right mdi mdi-download"></span>
            </div>
            <div v-if="service.attachments.length > 0">
                <attachment v-for="(attachment,key,index) in service.attachments" :key="key" :attachment="attachment"
                            @delete-attachment="deleteAttachment(attachment, key)" allow-delete />
            </div>
        </div>
        <div v-else class="alert alert-info">Zu dieser Veranstaltung gibt es keine Dateianhänge.</div>
        <hr/>
        <h3>Dateien hinzufügen</h3>
        <div v-if="uploading">Datei wird hochgeladen... <span class="mdi mdi-spin mdi-loading"></span></div>
        <form-file-uploader :parent="myService"
                            :upload-route="route('service.attach', this.myService.slug)"
                            v-model="myService.attachments"/>

        <template v-for="sheet in configurableLiturgySheets" :key="'dlg' + sheet.key">
            <modal
                v-if="dialogs[sheet.key]"
                :title="dialogTitle(sheet)"
                :allow-cancel="!sheet.configurationCloseOnly"
                @close="handleDialogClose(sheet)"
                @cancel="dialogs[sheet.key] = false"
                :close-button-label="dialogCloseButtonLabel(sheet)"
                cancel-button-label="Abbrechen"
            >
                <component :is="sheet.configurationComponent" :service="service" :sheet="sheet" />
            </modal>
        </template>
    </div>
</template>

<script>
import Attachment from "../../Ui/elements/Attachment";
import FormInput from "../../Ui/forms/FormInput";
import FormGroup from "../../Ui/forms/FormGroup";
import FormFileUpload from "../../Ui/forms/FormFileUpload";
import Modal from "../../Ui/modals/Modal";
import FormFileUploader from "../../Ui/forms/FormFileUploader";
import FullTextLiturgySheetConfiguration from "../../LiturgyEditor/LiturgySheets/FullTextLiturgySheetConfiguration";
import SongPPTLiturgySheetConfiguration from "../../LiturgyEditor/LiturgySheets/SongPPTLiturgySheetConfiguration";
import SongSheetLiturgySheetConfiguration from "../../LiturgyEditor/LiturgySheets/SongSheetLiturgySheetConfiguration";
import SBLiturgySheetConfiguration from "../../LiturgyEditor/LiturgySheets/SBLiturgySheetConfiguration.vue";
import A4WordSpecificLiturgySheetConfiguration
    from "../../LiturgyEditor/LiturgySheets/A4WordSpecificLiturgySheetConfiguration.vue";
import SongMailLiturgySheetDialog from "../../LiturgyEditor/LiturgySheets/SongMailLiturgySheetDialog.vue";
import AIPromptLiturgySheetDialog from "../../LiturgyEditor/LiturgySheets/AIPromptLiturgySheetDialog.vue";

export default {
    name: "AttachmentsTab",
    components: {
        FormFileUploader,
        Modal,
        FormFileUpload,
        FormGroup,
        FormInput,
        Attachment,
        A4WordSpecificLiturgySheetConfiguration,
        FullTextLiturgySheetConfiguration,
        SongPPTLiturgySheetConfiguration,
        SongSheetLiturgySheetConfiguration,
        SBLiturgySheetConfiguration,
        SongMailLiturgySheetDialog,
        AIPromptLiturgySheetDialog,
    },
    props: {
        service: Object,
        liturgySheets: Object,
        files: Object,
    },
    computed: {
        downloadableLiturgySheets() {
            return Object.values(this.liturgySheets)
                .filter(sheet => sheet && !sheet.isNotAFile);
        },
        configurableLiturgySheets() {
            return this.downloadableLiturgySheets
                .filter(sheet => sheet.configurationComponent);
        },
        hasAutoAttachments() {
            return this.myService.event_class == 'service';
        },
        hasAnnouncements() {
            if (this.myService.event_class != 'service') return false;
            let found = false;
            this.service.attachments.forEach(attachment => {
                found = found || (attachment.title == 'Bekanntgaben');
            });
            for (const sheetKey in this.liturgySheets) {
                found = found || (sheetKey == 'Schriftlesung und Abkündigungen');
            }
            return found;
        },
    },
    data() {
        return {
            myService: this.service,
            uploading: false,
            dialogs: this.createDialogs(this.liturgySheets),
        }
    },
    watch: {
        liturgySheets(newSheets) {
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
        downloadSheet(sheet) {
            if (sheet.configurationPage) {
                this.$inertia.visit(route('liturgy.configure', {service: this.service.slug, key: sheet.key}));
            } else {
                window.location.href = route('liturgy.download', {service: this.service.slug, key: sheet.key});
            }
        },
        downloadConfiguredSheet(sheet) {
            document.getElementById('frm'+sheet.key).submit();
            this.dialogs[sheet.key] = false;
        },
        handleDialogClose(sheet) {
            if (sheet.configurationCloseOnly) {
                this.dialogs[sheet.key] = false;
                return;
            }

            this.downloadConfiguredSheet(sheet);
        },
        dialogTitle(sheet) {
            if (sheet.configurationCloseOnly) return sheet.title;
            return sheet.title + ' herunterladen';
        },
        dialogCloseButtonLabel(sheet) {
            if (sheet.configurationCloseOnly) return 'Schließen';
            return 'Herunterladen';
        },
        downloadQR() {
            window.location.href = route('report.step', {report: 'KonfiAppQR', step: 'single', service: this.myService.id});
        },
        downloadAnnouncements() {
            window.location.href = route('report.step', {report: 'Announcements', step: 'auto', service: this.myService.id});
        },
        newRow() {
            this.files.attachment_text.push('');
            this.files.attachments.push(null);
            this.$forceUpdate();
        },
        deleteAttachment(attachment, key) {
            axios.delete(route('service.detach', {service: this.myService.slug, attachment: attachment.id}))
            .then(response => {
                this.myService.attachments = response.data;
            });
        },
        upload(file) {
            let title = file.name;
            title = title.substr(0, title.lastIndexOf('.'));
            title = title.charAt(0).toUpperCase() + title.slice(1);
            title = window.prompt('Bitte gib eine Beschreibung zu dieser Datei an.', title);
            if (null == title) return;

            let fd = new FormData();
            fd.append('attachment_text[0]', title);
            fd.append('attachments[0]', file);

            this.uploading = true;
            axios.post(route('service.attach', this.service.slug), fd, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.myService.attachments = response.data;
                this.uploading = false;
            });

        }
    }
}
</script>

<style scoped>
.liturgy-sheet {
    width: 100%;
    text-align: left;
    margin-bottom: .25rem;
}

.liturgy-sheet small {
    padding-left: 15px;
}

.mdi-download {
    color: white;
    float: right;
}

.uploader {
    width: 100%;
    min-height: 50px;
}

.alert a.btn {
    text-decoration: none;
}
</style>

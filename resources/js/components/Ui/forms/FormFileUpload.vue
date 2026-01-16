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
    <div>
    <form-group :name="name" :label="label" :help="help" :key="tabUpdate">
        <ul class="nav nav-pills mb-1" v-if="!noSourceTabs">
            <li class="nav-item">
                <a class="nav-link" :class="{active: showTab == 0}" @click="showTab = 0">Lokale Dateien</a>
            </li>
            <li class="nav-item" v-if="!noUrl">
                <a class="nav-link" :class="{active: showTab == 1}" @click="showTab = 1">Aus dem Internet</a>
            </li>
            <li class="nav-item" v-if="!noCamera">
                <a class="nav-link" :class="{active: showTab == 2}" @click="showTab = 2">Von der Kamera</a>
            </li>
            <li class="nav-item" v-if="showInbox">
                <a class="nav-link" :class="{active: showTab == 3}" @click="showTab = 3">Aus der Ablage</a>
            </li>
            <li class="nav-item" v-if="!noPixabay">
                <a class="nav-link" :class="{active: showTab == 4}" @click="showTab = 4">Von Pixabay</a>
            </li>
        </ul>
        <div class="upload-container" v-if="showTab == 0">
            <div class="drop-target" :key="dragging" :class="{dragging: dragging}" @dragenter.prevent="dragEnter"
                 @dragover.prevent
                 @dragleave.prevent="dragLeave"
                 @drop.prevent="drop">
                <input class="form-control-file"
                       type="file" :name="name" @change="handleInput" :multiple="multi"
                       @dragenter="dragEnter" @dragleave="dragLeave" @drop="drop"/>
                Hier klicken oder {{ multi ? 'Dateien' : 'Datei' }} hierher ziehen... {{ helpText || '' }}
            </div>
        </div>
        <div v-if="showTab == 1">
            <form-input label="Url der Datei" v-model="uploadUrl" />
            <form-input v-if="!noDescription" label="Beschreibung" v-model="description" />
            <nav-button icon="mdi-upload" @click="uploadFromUrl" :disabled="(!uploadUrl)">Von URL laden</nav-button>
        </div>
        <div class="upload-container" v-if="showTab == 2">
            <div class="drop-target" :key="dragging" :class="{dragging: dragging}">
                <input class="form-control-file" accept="image/*" capture="camera"
                       type="file" @change="handleInput" />
                Hier klicken, um ein Bild mit der Kamera aufzunehmen
            </div>
        </div>
        <div  v-if="showTab == 3">
            <div class="row inbox-row" v-for="inboxFile in inboxFiles" @click="uploadFromInbox(inboxFile)">
                <div class="col-md-8"><b><span :class="inboxFile.icon"></span> {{ inboxFile.name }}</b></div>
                <div class="col-md-2 text-muted">{{ moment(inboxFile.date).format('DD.MM.YYYY HH:mm') }}</div>
                <div class="col-md-2 text-right text-muted" style="text-align: right">{{ fileSize(inboxFile.filesize) }}</div>
            </div>
            <hr />
            <nav-button icon="mdi-refresh" @click="refreshInboxIndex">Ablage neu einlesen</nav-button>
        </div>
        <div  v-if="showTab == 4">
            <form-pixabay-picker @input="uploadFromPixabay" />
        </div>
    </form-group>
    </div>
</template>

<script>
import FormGroup from "./FormGroup";
import NavButton from "../buttons/NavButton";
import FormInput from "./FormInput";
import FormPixabayPicker from "./FormPixabayPicker";
import __ from 'lodash';

export default {
    name: "FormFileUpload",
    props: ['name', 'label', 'help', 'multiple', 'helpText', 'noCamera', 'noUrl', 'noPixabay', 'noDescription', 'noInbox', 'noSourceTabs', 'cut'],
    components: {FormPixabayPicker, FormInput, NavButton, FormGroup},
    data() {
        return {
            dragging: false,
            multi: this.multiple ? true : false,
            showTab: 0,
            uploadUrl: '',
            description: '',
            info: null,
            inboxFiles: [],
            showInbox: !this.noInbox,
            tabUpdate: 0,
        }
    },
    mounted() {
        this.refreshInboxIndex();
    },
    methods: {
        handleInput(event) {
            if (this.multi) {
                Array.from(event.target.files).forEach(file => {
                    this.$emit('input', file)
                });
            } else {
                this.$emit('input', event.target.files[0])
            }
            this.dragging = false;
        },
        dragEnter(e) {
            this.dragging = true;
        },
        dragLeave(e) {
            this.dragging = false;
        },
        async drop(e) {
            console.log('drop', e);
            this.dragging = false;

            // 1) Dropped file(s)
            const files = e.dataTransfer?.files;
            if (files && files.length) {
                if (this.multi) {
                    Array.from(files).forEach(file => this.$emit('input', file));
                } else {
                    this.$emit('input', files[0]);
                }
                return;
            }

            // 2) Dropped URL (e.g. dragging an <img> or link)
            const uri = e.dataTransfer?.getData('text/uri-list') || e.dataTransfer?.getData('text/plain');
            if (uri && /^https?:\/\//i.test(uri.trim())) {
                this.uploadUrl = uri.trim();
                this.uploadFromUrl();
                return;
            }

        },
        /**
         * Get human-readable file size
         * @param size
         * @returns {string}
         * @source https://programanddesign.com/js/human-readable-file-size-in-javascript/
         */
        fileSize(size) {
            var i = Math.floor(Math.log(size) / Math.log(1024));
            return (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
        },
        refreshInboxIndex() {
            this.$api().get(route('api.inbox.index')).then(response => {
                if (!response.data) {
                    this.showInbox = false;
                } else {
                    this.inboxFiles = response.data;
                    this.showInbox = true;
                    this.tabUpdate++;
                    this.$forceUpdate();
                }
            });
        },
        uploadFromUrl() {
            this.$emit('upload-url', {url: this.uploadUrl, description: this.description, info: this.info});
        },
        uploadFromPixabay(e) {
            if (!this.noDescription) this.description = window.prompt('Bitte gib eine Beschreibung für die Bilddatei an.');
            this.uploadUrl = e.fullHDURL;
            this.info = e;
            this.uploadFromUrl();
        },
        uploadFromInbox(e) {
            this.$emit('upload-inbox', e);
        }
    }
}
</script>

<style scoped lang="scss">
@use '../../../../sass/theme' as theme;



.upload-container {
    display: inline-block;
    position: relative;
    height: 6em;
    width: 100%;
}

.drop-target {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
     border-radius: 0;
    font-weight: bold;
    color: darkgray;

    min-height: 6rem;
    vertical-align: center;
    text-align: center;
    border: solid 1px rgb(206, 212, 218);
    background-color: #e9ecef;
}

.drop-target.dragging {
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control-file {
    position: absolute;
    left: 0;
    opacity: 0;
    top: 0;
    bottom: 0;
    width: 100%;
}

.nav-pills {
    font-size: .8em;
}

.inbox-row:nth-child(odd) {
    background-color: #f2f2f2;
}

.inbox-row:hover {
    background-color: theme.theme-color("primary") !important;
    color: white;
    cursor: pointer !important;
}

.inbox-row:hover .text-muted {
    color: white !important;
}


</style>

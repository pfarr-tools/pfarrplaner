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
    <div class="ads-tab">
        <div class="row">
            <div class="col-md-8">
                <form-textarea label="Kurzbeschreibung für Werbung" v-model="myService.ad_text" />
                <hr />
                <h3>Veröffentlichungen</h3>

                <table class="input-table" width="100%" :key="adConfigsUpdated">
                    <thead>
                    <tr>
                        <th>Ort</th>
                        <th>Vorlauf (Tage)</th>
                        <th>Abweichender Text</th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(adChannel, adChannelKey) in adChannels">
                            <th valign="top" width="30%">
                                <checked-process-item :check="myService.ad_configs[adChannelKey].offset > 0"
                                                      :key="myService.ad_configs[adChannelKey].offset">
                                    <template slot="negative">{{ adChannel.name }}</template>
                                    <template slot="positive">{{ adChannel.name }}</template>
                                </checked-process-item>
                            </th>
                            <td valign="top" width="10%">
                                <input class="form-control" :value="myService.ad_configs[adChannelKey].offset" type="number" min="0"
                                    @input="adConfigUpdateOffset(adChannelKey, $event)" />
                            </td>
                            <td valign="top" width="60%">
                                <form-textarea v-model="myService.ad_configs[adChannelKey].ad_text" :rows="1"
                                    :disabled="!myService.ad_configs[adChannelKey].offset"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-4">
                <h2>Verfügbare Bilder</h2>
                <div class="row">
                    <div
                        class="col-6 col-md-3 col-lg-2 mb-3"
                        v-for="attachment in myService.attachments"
                        :key="attachment.id || attachment.file"
                        v-if="!attachment.cut"
                    >
                        <div class="thumb-wrapper">
                            <div class="thumb border border-light-subtle p-1">
                                <div class="thumb-inner rounded">
                                    <img
                                        :src="imageRoute(attachment.file)"
                                        class="thumb-img"
                                        alt=""
                                    >
                                </div>
                            </div>

                            <!-- Hover Preview -->
                            <div class="thumb-preview shadow-lg">
                                <img
                                    :src="imageRoute(attachment.file)"
                                    alt=""
                                >
                            </div>
                        </div>
                    </div>                </div>
                <hr />
                <h2>Zuschnitte</h2>
                <div class="row" >
                    <div class="col-4 border border-light-subtle p-1" v-for="(size,title) in config.images.cuts">
                        <div class="row">
                            <div class="col-8 cut-title">
                                <div class="fs-7 text-muted text-start">
                                    <div class="fw-bold">{{ title }}</div>
                                    {{ size[0] }}x{{ size[1] }}
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <nav-button class="btn-sm mb-1" v-if="hasCutImage(slug(title))" icon="mdi mdi-delete"
                                            type="danger" title="Zuordnung löschen" @click="deleteAttachment(getCutAttachment(slug(title)))"
                                            :force-icon="true" :force-no-text="true"/>
                            </div>
                        </div>
                        <img v-if="hasCutImage(slug(title))" :src="imageRoute(getCutImage(slug(title)))" class="img-fluid" />
                        <form-file-uploader v-else :parent="myService"
                                            :upload-route="route('service.attach', myService.slug)"
                                            v-model="myService.attachments" :width="size[0]" :height="size[1]"
                                            :cut="slug(title)" :no-description="true" :no-camera="true" :no-pixabay="true"
                                            :no-inbox="true" :no-url="true" :noSourceTabs="true"
                                            help-text="(z.B. Bilder oben aus der Vorschau)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>


import slug from '../../../libraries/Slug';
import FormFileUploader from "../../Ui/forms/FormFileUploader.vue";
import NavButton from "../../Ui/buttons/NavButton.vue";
import FormTextarea from "../../Ui/forms/FormTextarea.vue";
import FormGroup from "../../Ui/forms/FormGroup.vue";
import FormInput from "../../Ui/forms/FormInput.vue";
import CheckedProcessItem from "../../Ui/elements/CheckedProcessItem.vue";

export default {
    name: "AdsTab",
    components: {
        CheckedProcessItem,
        FormInput,
        FormGroup,
        FormTextarea,
        NavButton,
        FormFileUploader
    },
    props: {
        service: Object,
        config: Object,
        adChannels: Object,
    },
    computed: {
        disabled() {
        }
    },
    data() {
        /**
         * default Ads channels
         * bekanntgaben - Bekanntgaben: extra erwähnen (Folie / Text)
         * newsletter - Newsletter: Feature (Bild + Text)
         * communiapp - CommuniApp: eigene Veranstaltung
         * web - Homepage: Feature auf der Startseite
         * blaettle - Blättle: Text
         */

        let myService = this.service;
        if (undefined === myService.ad_text) myService.ad_text = '';
        if (undefined === myService.ad_configs) myService.ad_configs = {};

        // reformat ad_configs to be compatible with our ad channels format
        let myAdConfigs = {};
        for (const adConfigKey in myService.ad_configs) {
            myAdConfigs[myService.ad_configs[adConfigKey].slug] = {
                offset: myService.ad_configs[adConfigKey].offset || null,
                ad_text: myService.ad_configs[adConfigKey].ad_text || '',
            }
        }
        myService.ad_configs = myAdConfigs;

        // add missing ad channels to ad_configs
        for (const adChannelKey in this.adChannels) {
            if (undefined === myService.ad_configs[adChannelKey]) {
                myService.ad_configs[adChannelKey] = {
                    offset: null,
                    ad_text: '',
                }
            }
        }

        return {
            myService,
            slug: slug,
            adConfigsUpdated: 0,
            adChannelsUpdated: 0,
        }
    },
    methods: {
        imageRoute(url) {
            if (!url) return null;
            return route('image', {path: url.replace('attachments/', '')});
        },
        getCutImage(cut) {
            let f = null;
            this.myService.attachments.forEach(attachment => {
                if (attachment.cut == cut) f = attachment.file;
            });
            return f;
        },
        getCutAttachment(cut) {
            let f = null;
            this.myService.attachments.forEach(attachment => {
                if (attachment.cut == cut) f = attachment;
            });
            return f;
        },
        hasCutImage(cut) {
            let f = false
            this.myService.attachments.forEach(attachment => {
                if (attachment.cut == cut) f = true;
            });
            return f;
        },
        deleteAttachment(attachment) {
            axios.delete(route('service.detach', {service: this.myService.slug, attachment: attachment.id}))
                .then(response => {
                    this.myService.attachments = response.data;
                });
        },
        adConfigUpdateOffset(adChannelKey, e) {
            if (e.target.value > 0) {
                this.myService.ad_configs[adChannelKey].offset = parseInt(e.target.value);
            } else {
                this.myService.ad_configs[adChannelKey].offset = null;

            }
            this.myService.adConfigsUpdated++;
            this.$forceUpdate();
        }
    }
}
</script>

<style scoped>
.thumb-wrapper {
    position: relative;
}

/* Thumbnail */
.thumb-inner {
    position: relative;
    width: 100%;
    padding-top: 100%;      /* Quadrat */
    overflow: hidden;
}

.thumb-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;      /* gecroppt */
}

/* Preview */
.thumb-preview {
    position: absolute;
    top: 100%;              /* unterhalb des Thumbnails */
    right: 0;               /* rechts ausgerichtet */
    margin-top: 0.5rem;

    width: 260px;
    max-height: 260px;
    padding: 0.5rem;

    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);

    z-index: 1000;

    opacity: 0;
    pointer-events: none;
    transform: scale(0.95);
    transform-origin: top right;
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.thumb-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;    /* kein Cropping */
    display: block;
}

/* Hover-Effekt */
.thumb-wrapper:hover .thumb-preview {
    opacity: 1;
    transform: scale(1);
}

.cut-title {
    font-size: .7em;
}
</style>

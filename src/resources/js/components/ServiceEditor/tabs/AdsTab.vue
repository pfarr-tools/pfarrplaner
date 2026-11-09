<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -
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
        <tab-headers class="ads-section-tabs">
            <tab-header
                id="images"
                title="Werbebilder"
                :active-tab="activeSection"
                :switch-handler="true"
                @tab="activeSection = $event"
            />
            <tab-header
                id="publications"
                title="Veröffentlichungen"
                :active-tab="activeSection"
                :switch-handler="true"
                @tab="activeSection = $event"
            />
        </tab-headers>
        <tabs>
            <tab
                id="images"
                :active-tab="activeSection"
            >
                <div class="asset-layout">
                    <section class="asset-upload-panel card shadow-sm">
                        <div class="card-header">
                            <h2 class="h4 mb-1">Bilder hochladen</h2>
                            <small class="text-muted"
                                >Eine Quelle kann für mehrere Zielgrößen verwendet werden.</small
                            >
                        </div>
                        <div class="card-body">
                            <form-file-uploader
                                ref="uploader"
                                :parent="myService"
                                :upload-route="route('service.attach', myService.slug)"
                                :chunk-upload-route="route('api.attachment-uploads.start')"
                                :chunk-target="{
                                    target_type: 'App\\Models\\Service',
                                    target_id: myService.id,
                                }"
                                v-model="myService.attachments"
                                :presets="imagePresets"
                                help-text="Bilder hierher ziehen oder auswählen"
                            />
                            <div
                                v-if="sourceAttachments.length"
                                class="existing-image-sources mt-4"
                            >
                                <h3 class="h6">Vorhandene Bild-Attachments</h3>
                                <p class="small text-muted">
                                    Wähle ein Ziel, um ein vorhandenes Bild lokal neu zuzuschneiden.
                                </p>
                                <div class="existing-image-source-list">
                                    <article
                                        v-for="attachment in sourceAttachments"
                                        :key="attachment.id || attachment.file"
                                        class="existing-image-source"
                                    >
                                        <img
                                            :src="imageRoute(attachment.file)"
                                            :alt="attachment.title || 'Bildquelle'"
                                        />
                                        <div class="min-w-0 flex-grow-1">
                                            <strong class="d-block text-truncate">{{
                                                attachment.title || attachment.file
                                            }}</strong>
                                            <select
                                                v-model="sourceTargets[sourceKey(attachment)]"
                                                class="form-select form-select-sm mt-1"
                                                :aria-label="`Ziel für ${attachment.title || 'Bildquelle'}`"
                                            >
                                                <option value="">Ziel auswählen …</option>
                                                <option
                                                    v-for="preset in imagePresets"
                                                    :key="preset.cut"
                                                    :value="preset.cut"
                                                >
                                                    {{ preset.title }}
                                                </option>
                                            </select>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            :disabled="!sourceTargets[sourceKey(attachment)]"
                                            @click="useExistingSource(attachment)"
                                        >
                                            <span
                                                class="mdi mdi-crop"
                                                aria-hidden="true"
                                            ></span
                                            ><span class="visually-hidden"
                                                >Als Quelle verwenden</span
                                            >
                                        </button>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="asset-panel card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="h4 mb-1">Zuschnitte</h2>
                                <small class="text-muted"
                                    >Vordefinierte Größen für die Veröffentlichungen</small
                                >
                            </div>
                            <span class="text-muted small"
                                >{{ imagePresets.length }} Zielgrößen</span
                            >
                        </div>
                        <div class="card-body">
                            <div class="asset-target-list">
                                <article
                                    v-for="preset in imagePresets"
                                    :key="preset.cut"
                                    class="asset-target card"
                                >
                                    <div class="card-body p-2">
                                        <div>
                                            <strong>{{ preset.title }}</strong
                                            ><small class="d-block text-muted"
                                                >{{ preset.width }}×{{ preset.height }} ·
                                                {{ preset.aspectRatio.toFixed(2) }}:1</small
                                            >
                                        </div>
                                        <div
                                            class="asset-preview mt-2"
                                            :class="{ 'is-empty': !hasCutImage(preset.cut) }"
                                        >
                                            <img
                                                v-if="hasCutImage(preset.cut)"
                                                :src="imageRoute(getCutImage(preset.cut))"
                                                :alt="preset.title"
                                            />
                                            <span
                                                v-else
                                                class="text-muted"
                                                ><span
                                                    class="mdi mdi-image-plus"
                                                    aria-hidden="true"
                                                ></span>
                                                Kein Zuschnitt</span
                                            >
                                        </div>
                                        <button
                                            v-if="hasCutImage(preset.cut)"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            @click="deleteAttachment(getCutAttachment(preset.cut))"
                                        >
                                            <span
                                                class="mdi mdi-delete"
                                                aria-hidden="true"
                                            ></span>
                                            Löschen
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>
                </div>
            </tab>
            <tab
                id="publications"
                :active-tab="activeSection"
            >
                <div
                    class="publication-editor"
                    :key="adConfigsUpdated"
                >
                    <nav
                        class="publication-tabs nav nav-pills flex-column"
                        aria-label="Werbekanäle"
                    >
                        <button
                            v-for="publication in publicationCards"
                            :key="publication.key"
                            type="button"
                            class="publication-tab nav-link text-start"
                            :class="{ active: activeAdChannel === publication.key }"
                            :aria-selected="activeAdChannel === publication.key"
                            @click="activeAdChannel = publication.key"
                        >
                            <span
                                class="publication-tab-status mdi"
                                :class="
                                    publication.isGeneral
                                        ? 'mdi-information-outline text-primary'
                                        : publication.config.offset > 0
                                          ? 'mdi-check-circle text-success'
                                          : 'mdi-close-circle text-muted'
                                "
                                aria-hidden="true"
                            ></span>
                            <span class="publication-tab-label">{{ publication.name }}</span>
                        </button>
                    </nav>
                    <div
                        v-if="activePublication && !activePublication.isGeneral"
                        class="publication-settings card"
                    >
                        <div class="card-body">
                            <h4 class="h6">Ausgabe bearbeiten</h4>
                            <label
                                class="form-label small mb-1"
                                :for="`publication-offset-${activePublication.key}`"
                                >Vorlauf (Tage)</label
                            >
                            <input
                                :id="`publication-offset-${activePublication.key}`"
                                class="form-control form-control-sm"
                                :value="activePublication.config.offset"
                                type="number"
                                min="0"
                                :aria-label="`Vorlauf in Tagen für ${activePublication.name}`"
                                @input="adConfigUpdateOffset(activePublication.key, $event)"
                            />
                            <label
                                class="form-label small mb-1 mt-3"
                                :for="`publication-text-${activePublication.key}`"
                                >Abweichender Text</label
                            >
                            <textarea
                                :id="`publication-text-${activePublication.key}`"
                                class="form-control form-control-sm"
                                rows="6"
                                :value="activePublication.text"
                                :aria-label="`Abweichender Text für ${activePublication.name}`"
                                :disabled="!activePublication.config.offset"
                                @input="
                                    myService.ad_configs[activePublication.key].ad_text =
                                        $event.target.value
                                "
                            />
                        </div>
                    </div>
                    <article
                        v-if="activePublication"
                        class="publication-card card"
                    >
                        <div class="card-body">
                            <div
                                v-if="activePublication.isGeneral"
                                class="general-publication-content"
                            >
                                <h4 class="h6 mb-2">Allgemeine Werbeeinstellungen</h4>
                                <p class="text-muted small">
                                    Dieser Text wird verwendet, wenn ein Veröffentlichungskanal
                                    keinen eigenen Text hat.
                                </p>
                                <form-textarea
                                    label="Kurzbeschreibung für Werbung"
                                    v-model="myService.ad_text"
                                />
                            </div>
                            <template v-else>
                                <div class="mb-3">
                                    <h4 class="h6 mb-1">{{ activePublication.name }}</h4>
                                    <div class="small text-muted">
                                        Bild-Cut:
                                        <strong>{{ activePublication.preset.title }}</strong> ·
                                        {{ activePublication.preset.width }}×{{
                                            activePublication.preset.height
                                        }}
                                    </div>
                                </div>
                                <div
                                    class="publication-preview"
                                    :class="`publication-preview-${activePublication.preset.cut}`"
                                >
                                    <img
                                        v-if="activePublication.attachment"
                                        :src="imageRoute(activePublication.attachment.file)"
                                        :alt="activePublication.preset.title"
                                    />
                                    <div
                                        v-else
                                        class="publication-preview-empty"
                                    >
                                        <span
                                            class="mdi mdi-image-plus"
                                            aria-hidden="true"
                                        ></span
                                        ><span>Noch kein passender Zuschnitt</span>
                                    </div>
                                    <div class="publication-preview-overlay">
                                        <strong>{{ myService.title || 'Gottesdienst' }}</strong
                                        ><span>{{
                                            activePublication.text ||
                                            myService.ad_text ||
                                            'Veranstaltungsbeschreibung'
                                        }}</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </article>
                </div>
            </tab>
        </tabs>
    </div>
</template>

<script>
import slug from '@pfarr.tools/slug'
import FormFileUploader from '../../Ui/forms/FormFileUploader.vue'
import FormTextarea from '../../Ui/forms/FormTextarea.vue'
import TabHeaders from '../../Ui/tabs/tabHeaders.vue'
import TabHeader from '../../Ui/tabs/tabHeader.vue'
import Tabs from '../../Ui/tabs/tabs.vue'
import Tab from '../../Ui/tabs/tab.vue'
import { createAdsImagePresets } from '../adsImagePresets.js'

export default {
    name: 'AdsTab',
    components: {
        FormTextarea,
        FormFileUploader,
        TabHeaders,
        TabHeader,
        Tabs,
        Tab,
    },
    props: {
        service: Object,
        config: Object,
        adChannels: Object,
    },
    computed: {
        imagePresets() {
            return createAdsImagePresets(this.config?.images?.cuts, slug)
        },
        sourceAttachments() {
            return (this.myService.attachments || []).filter((attachment) =>
                this.isImageAttachment(attachment),
            )
        },
        publicationCards() {
            const cards = [
                {
                    key: 'general',
                    name: 'Allgemein',
                    isGeneral: true,
                    config: { offset: null },
                    text: this.myService.ad_text || '',
                    preset: null,
                    attachment: null,
                },
            ]
            return cards.concat(
                Object.entries(this.adChannels || {}).map(([key, channel]) => {
                    const preset = this.presetForChannel(key)
                    const config = this.myService.ad_configs[key]
                    return {
                        key,
                        name: channel.name,
                        preset,
                        config,
                        text: config?.ad_text || '',
                        attachment: this.getCutAttachment(preset.cut),
                    }
                }),
            )
        },
        activePublication() {
            return (
                this.publicationCards.find(
                    (publication) => publication.key === this.activeAdChannel,
                ) ||
                this.publicationCards[0] ||
                null
            )
        },
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

        let myService = this.service
        if (undefined === myService.ad_text) myService.ad_text = ''
        if (undefined === myService.ad_configs) myService.ad_configs = {}

        // reformat ad_configs to be compatible with our ad channels format
        let myAdConfigs = {}
        for (const adConfigKey in myService.ad_configs) {
            myAdConfigs[myService.ad_configs[adConfigKey].slug] = {
                offset: myService.ad_configs[adConfigKey].offset || null,
                ad_text: myService.ad_configs[adConfigKey].ad_text || '',
            }
        }
        myService.ad_configs = myAdConfigs

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
            activeAdChannel: 'general',
            activeSection: 'images',
            sourceTargets: {},
        }
    },
    methods: {
        imageRoute(url) {
            if (!url) return null
            return route('image', { path: url.replace('attachments/', '') })
        },
        getCutImage(cut) {
            return (
                this.myService.attachments.find((attachment) => attachment.cut == cut)?.file || null
            )
        },
        getCutAttachment(cut) {
            return this.myService.attachments.find((attachment) => attachment.cut == cut) || null
        },
        hasCutImage(cut) {
            return this.myService.attachments.some((attachment) => attachment.cut == cut)
        },
        /** Identify image attachments that can be used as local crop sources. */
        isImageAttachment(attachment) {
            return Boolean(
                attachment?.mimeType?.startsWith('image/') ||
                /\.(avif|gif|jpe?g|png|svg|webp)$/i.test(attachment?.file || ''),
            )
        },
        /** Return the stable key used for source target selections. */
        sourceKey(attachment) {
            return attachment.id || attachment.file
        },
        /** Select the default image cut used by a publication channel. */
        presetForChannel(channelKey) {
            const preferredCuts = {
                story: 'story',
                communiapp: 'communiapp',
                newsletter: 'bildschirm-16x9',
                ppt: 'bildschirm-4x3',
                bekanntgaben: 'bildschirm-4x3',
            }
            return (
                this.imagePresets.find((preset) => preset.cut === preferredCuts[channelKey]) ||
                this.imagePresets[0] || { cut: '', title: 'Kein Bild-Cut', width: 1, height: 1 }
            )
        },
        /** Open an existing image in the cropper with its selected target preset. */
        useExistingSource(attachment) {
            const preset = this.imagePresets.find(
                (candidate) => candidate.cut === this.sourceTargets[this.sourceKey(attachment)],
            )
            if (preset) this.$refs.uploader.reuseAttachment(attachment, preset)
        },
        deleteAttachment(attachment) {
            if (!confirm('Willst du diese Datei wirklich löschen?')) return
            axios
                .delete(
                    route('service.detach', {
                        service: this.myService.slug,
                        attachment: attachment.id,
                    }),
                )
                .then((response) => {
                    this.myService.attachments = response.data
                })
        },
        adConfigUpdateOffset(adChannelKey, e) {
            if (e.target.value > 0) {
                this.myService.ad_configs[adChannelKey].offset = parseInt(e.target.value)
            } else {
                this.myService.ad_configs[adChannelKey].offset = null
            }
            this.adConfigsUpdated++
        },
    },
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
    padding-top: 100%; /* Quadrat */
    overflow: hidden;
}

.thumb-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover; /* gecroppt */
}

/* Preview */
.thumb-preview {
    position: absolute;
    top: 100%; /* unterhalb des Thumbnails */
    right: 0; /* rechts ausgerichtet */
    margin-top: 0.5rem;

    width: 260px;
    max-height: 260px;
    padding: 0.5rem;

    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);

    z-index: 1000;

    opacity: 0;
    pointer-events: none;
    transform: scale(0.95);
    transform-origin: top right;
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.thumb-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* kein Cropping */
    display: block;
}

/* Hover-Effekt */
.thumb-wrapper:hover .thumb-preview {
    opacity: 1;
    transform: scale(1);
}

.cut-title {
    font-size: 0.7em;
}

.ad-target-card {
    min-height: 10rem;
}

.ad-target-empty {
    min-height: 7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-align: center;
}

.asset-layout {
    display: grid;
    grid-template-columns: minmax(18rem, 0.8fr) minmax(0, 1.5fr);
    gap: 1rem;
    align-items: start;
}
.asset-upload-panel,
.asset-panel {
    border-color: var(--bs-border-color);
}
.asset-target-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}
.asset-target {
    border-color: var(--bs-border-color);
}
.asset-preview {
    display: grid;
    min-height: 8rem;
    place-items: center;
    overflow: hidden;
    border-radius: var(--bs-border-radius-sm);
    background: #f8f9fa;
    background-image:
        linear-gradient(45deg, #e1e5e9 25%, transparent 25%),
        linear-gradient(-45deg, #e1e5e9 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #e1e5e9 75%),
        linear-gradient(-45deg, transparent 75%, #e1e5e9 75%);
    background-size: 1rem 1rem;
    background-position:
        0 0,
        0 0.5rem,
        0.5rem -0.5rem,
        -0.5rem 0;
}
.asset-preview img {
    display: block;
    width: 100%;
    height: 10rem;
    object-fit: contain;
}
.asset-preview.is-empty {
    color: var(--bs-secondary-color);
}
.existing-image-source-list {
    display: grid;
    gap: 0.5rem;
}
.existing-image-source {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
    padding: 0.5rem;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius-sm);
}
.existing-image-source img {
    width: 3.5rem;
    height: 3.5rem;
    flex: 0 0 auto;
    object-fit: contain;
    border-radius: var(--bs-border-radius-sm);
    background: var(--bs-light-bg-subtle);
}
.existing-image-source .form-select {
    min-width: 0;
}

.publication-editor {
    display: grid;
    grid-template-columns: minmax(12rem, 15rem) minmax(0, 1fr) minmax(15rem, 0.8fr);
    gap: 1rem;
    align-items: start;
}
.publication-tabs {
    gap: 0.35rem;
}
.publication-tab {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    color: var(--bs-body-color);
}
.publication-tab-status {
    flex: 0 0 auto;
    margin-top: 0.15rem;
}
.publication-tab-label {
    min-width: 0;
}
.publication-card {
    border-color: var(--bs-border-color);
}
.publication-preview {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    min-height: 15rem;
    background: #20242b;
    color: #fff;
}
.publication-preview-story {
    min-height: 25rem;
    max-width: 18rem;
}
.publication-preview-communiapp {
    aspect-ratio: 2 / 1;
    min-height: 0;
}
.publication-preview-bildschirm-16x9 {
    aspect-ratio: 16 / 9;
    min-height: 0;
}
.publication-preview-bildschirm-4x3 {
    aspect-ratio: 4 / 3;
    min-height: 0;
}
.publication-preview img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.86;
}
.publication-preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    color: rgba(255, 255, 255, 0.65);
}
.publication-preview-empty .mdi {
    font-size: 2rem;
}
.publication-preview-overlay {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    padding: 2rem 1rem 0.75rem;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.78));
}
.publication-preview-overlay span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.publication-settings {
    min-width: 0;
}
@media (max-width: 767.98px) {
    .asset-layout {
        grid-template-columns: 1fr;
    }
    .asset-target-list {
        grid-template-columns: 1fr;
    }
    .publication-editor {
        grid-template-columns: 1fr;
    }
    .publication-tabs {
        flex-direction: row !important;
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    .publication-tab {
        min-width: 12rem;
    }
    .publication-preview-story {
        max-width: none;
    }
}

@media (max-width: 767px) {
    .ad-target-card {
        width: 100%;
    }
}
</style>

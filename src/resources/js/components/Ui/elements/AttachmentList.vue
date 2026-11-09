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
    <div
        class="attachment-list"
        :class="{ dragging: dragging }"
        @dragenter.prevent="dragEnter"
        @dragover.prevent
        @dragleave.prevent="dragLeave"
        @drop.prevent="handleDrop"
    >
        <form-file-uploader
            v-if="uploadRoute"
            ref="uploader"
            :upload-route="uploadRoute"
            :chunk-upload-route="chunkUploadRoute"
            :chunk-target="chunkTarget"
            :presets="presets"
            @drop.stop
            @queue-change="pendingUploads = $event"
            @update:model-value="updateAttachments"
        />
        <div
            v-if="autoAttachments.length || attachments.length"
            class="attachment-items"
        >
            <div
                v-for="attachment in autoAttachments"
                :key="'auto-' + attachment.key"
                class="attachment auto-attachment"
            >
                <div
                    class="attachment-content"
                    @click.prevent="$emit('open-auto-attachment', attachment)"
                >
                    <b
                        ><span
                            class="fa"
                            :class="attachment.icon"
                        ></span>
                        {{ attachment.title }}</b
                    ><br />
                    <small>.{{ attachment.extension }}, {{ attachment.size }}</small>
                </div>
                <div class="attachment-actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        title="Datei öffnen"
                        @click.prevent="$emit('open-auto-attachment', attachment)"
                    >
                        <span class="mdi mdi-download"></span>
                    </button>
                </div>
            </div>
            <attachment
                v-for="attachment in attachments"
                :key="attachment.id || attachment.file"
                :attachment="attachment"
                :inline-lightbox="false"
                @delete-attachment="deleteAttachment(attachment)"
                @show-lightbox="openLightbox(attachment)"
                allow-delete
            />
        </div>
        <div
            v-if="!(attachments.length || pendingUploads || preventEmptyListMessage)"
            class="alert alert-info"
        >
            {{ emptyMessage || 'Es sind noch keine Dateianhänge vorhanden.' }}
        </div>
        <attachment-lightbox
            v-if="lightboxIndex !== null"
            :attachments="imageAttachments"
            :index="lightboxIndex"
            @close="lightboxIndex = null"
            @previous="previousImage"
            @next="nextImage"
        />
    </div>
</template>

<script>
import Attachment from './Attachment'
import AttachmentLightbox from './AttachmentLightbox.vue'
import FormFileUploader from '../forms/FormFileUploader.vue'

export default {
    name: 'AttachmentList',
    emits: ['update:modelValue', 'open-auto-attachment'],
    components: { Attachment, AttachmentLightbox, FormFileUploader },
    props: {
        modelValue: { type: Array, default: () => [] },
        deleteRouteName: String,
        emptyMessage: String,
        parentType: String,
        parentRouteKey: { type: String, default: 'id' },
        parentObject: Object,
        preventEmptyListMessage: Boolean,
        uploadRoute: String,
        chunkUploadRoute: String,
        chunkTarget: Object,
        presets: { type: Array, default: () => [] },
        autoAttachments: { type: Array, default: () => [] },
    },
    data() {
        return {
            attachments: this.modelValue || [],
            dragging: false,
            lightboxIndex: null,
            pendingUploads: 0,
        }
    },
    computed: {
        imageAttachments() {
            return this.attachments.filter(
                (attachment) => attachment.hasFile && attachment.mimeType?.startsWith('image/'),
            )
        },
    },
    watch: {
        modelValue: {
            handler(value) {
                this.attachments = value || []
            },
            deep: true,
        },
    },
    methods: {
        deleteAttachment(attachment) {
            if (!confirm('Willst du diese Datei wirklich löschen?')) return
            let config = { attachment: attachment.id }
            config[this.parentType] = this.parentObject[this.parentRouteKey]
            axios.delete(route(this.deleteRouteName, config)).then((response) => {
                this.attachments = response.data
                this.$emit('update:modelValue', response.data)
            })
        },
        updateAttachments(attachments) {
            const incoming = attachments || []
            const byId = new Map(this.attachments.map((attachment) => [attachment.id, attachment]))
            incoming.forEach((attachment) => byId.set(attachment.id, attachment))
            this.attachments = [...byId.values()]
            this.$emit('update:modelValue', this.attachments)
        },
        dragEnter() {
            this.dragging = true
        },
        dragLeave() {
            this.dragging = false
        },
        handleDrop(event) {
            this.dragging = false
            const files = Array.from(event.dataTransfer?.files || [])
            if (files.length && this.$refs.uploader?.addFile) this.$refs.uploader.addFile(files)
            else if (files.length) this.$emit('drop-files', files)
        },
        openLightbox(attachment) {
            this.lightboxIndex = this.imageAttachments.findIndex(
                (item) => item.id === attachment.id,
            )
        },
        previousImage() {
            this.lightboxIndex =
                (this.lightboxIndex - 1 + this.imageAttachments.length) %
                this.imageAttachments.length
        },
        nextImage() {
            this.lightboxIndex = (this.lightboxIndex + 1) % this.imageAttachments.length
        },
    },
}
</script>

<style scoped>
.attachment-list.dragging {
    outline: 2px dashed #0d6efd;
    outline-offset: 0.35rem;
    background: rgba(13, 110, 253, 0.04);
}

.attachment-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.auto-attachment {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.5rem;
    width: 100%;
    margin-bottom: 0.25rem;
    padding: 0.75rem;
    text-align: left;
    vertical-align: middle;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    box-shadow: var(--bs-box-shadow-sm);
    cursor: pointer;
}

.auto-attachment .attachment-content {
    min-width: 0;
}
.auto-attachment .attachment-actions {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

@media (max-width: 575.98px) {
    .attachment-items {
        grid-template-columns: 1fr;
    }
}
</style>

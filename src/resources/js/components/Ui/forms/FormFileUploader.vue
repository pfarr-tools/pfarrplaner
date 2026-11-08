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
    <div class="form-file-uploader">
        <div v-if="uploading" class="upload-summary" aria-live="polite">
            {{ totalProgress }}% hochgeladen
            <span class="mdi mdi-spin mdi-loading" aria-hidden="true"></span>
        </div>
        <div class="upload-drop-surface" :class="{dragging: dragging}"
             @dragenter.prevent="dragEnter" @dragover.prevent="dragEnter"
             @dragleave.prevent="dragLeave" @drop.prevent="drop">
            <form-file-upload @input="addFile" @upload-url="uploadUrl" @upload-inbox="uploadInbox" @drop.stop multiple="1" :key="updateUploader"
                          :no-camera="noCamera" :no-description="noDescription" :no-pixabay="noPixabay" :no-url="noUrl" :no-inbox="noInbox"
                          :no-source-tabs="noSourceTabs" :cut="cut" :help-text="helpText"
            />
        </div>
        <div v-if="queue.length" class="upload-queue" aria-label="Upload-Warteschlange">
            <div v-for="item in queue" :key="item.id" class="upload-queue-item">
                <img v-if="isImage(item)" :src="item.previewUrl" class="queue-preview" :alt="item.title" />
                <span v-else class="queue-file-icon mdi mdi-file" aria-hidden="true"></span>
                <div class="queue-details">
                    <strong class="queue-title">{{ item.title }}</strong>
                    <div v-if="item.preset" class="queue-target badge text-bg-light">{{ item.preset.title }}</div>
                    <small class="text-muted">{{ item.file.name }} · {{ statusLabel(item) }}</small>
                    <div v-if="item.status === 'uploading' || item.status === 'error'" class="progress mt-1" role="progressbar"
                         :aria-valuenow="item.progress" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" :class="{'bg-danger': item.status === 'error'}"
                             :style="{width: `${item.progress}%`}">{{ item.progress }}%</div>
                    </div>
                    <small v-if="item.error" class="text-danger">{{ item.error }}</small>
                </div>
                <button v-if="item.status !== 'uploading'" type="button"
                        class="btn btn-sm btn-outline-secondary" title="Datei bearbeiten"
                        @click="openEditor(item)">
                    <span class="mdi" :class="isImage(item) ? 'mdi-crop' : 'mdi-pencil'" aria-hidden="true"></span>
                </button>
                <button v-if="item.status !== 'uploading'" type="button" class="btn btn-sm btn-outline-secondary"
                        title="Als Variante duplizieren" @click="duplicate(item)">
                    <span class="mdi mdi-content-copy" aria-hidden="true"></span>
                </button>
                <button v-if="item.status === 'error'" type="button" class="btn btn-sm btn-outline-warning"
                        @click="retry(item)">Erneut versuchen</button>
                <button v-if="item.status !== 'uploading'" type="button" class="btn btn-sm btn-outline-danger"
                        title="Aus Warteschlange entfernen" @click="remove(item)">
                    <span class="mdi mdi-delete" aria-hidden="true"></span>
                </button>
            </div>
            <div class="upload-queue-actions">
                <button type="button" class="btn btn-primary" :disabled="uploading || !readyItems.length" @click="uploadAll">
                    <span class="mdi mdi-upload" aria-hidden="true"></span> Alle hochladen
                </button>
                <span class="text-muted">{{ readyItems.length }} bereit</span>
            </div>
        </div>
        <modal :title="isEditingImage ? 'Bild bearbeiten' : 'Datei bearbeiten'" v-if="editingItem" min-height="50vh"
               :allow-close="false" :allow-cancel="false" max-width="1000">
            <div class="edit-file-meta">
                <label class="form-label" for="editing-file-title">Titel / Beschreibung</label>
                <input id="editing-file-title" v-model="editingItem.title" class="form-control" type="text" />
                <small class="text-muted">{{ editingItem.file.name }}</small>
            </div>
            <attachment-image-editor v-if="isEditingImage" :file="editingItem.sourceFile || editingItem.file"
                                     :width="editingItem.preset?.width || width"
                                     :height="editingItem.preset?.height || height"
                                     :preset="editingItem.preset"
                                     :presets="presets"
                                     :initial-transform="editingItem.transform"
                                     @apply="applyEditorResult" @cancel="cancelEditing" />
            <div v-else class="generic-file-editor">
                <div class="file-preview-placeholder">
                    <span class="mdi mdi-file-outline" aria-hidden="true"></span>
                    <span>{{ editingItem.file.type || 'Datei' }}</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-link" @click="cancelEditing">Abbrechen</button>
                    <button type="button" class="btn btn-primary" @click="applyFileDetails">Übernehmen</button>
                </div>
            </div>
        </modal>
    </div>
</template>

<script>
import FormFileUpload from "./FormFileUpload";
import Modal from "../modals/Modal";
import AttachmentImageEditor from './AttachmentImageEditor.vue';
import { appendFiles, duplicateUploadItem } from './attachmentUploadQueue.js';
import { uploadFileInChunks } from './resumableUpload.js';


export default {
    name: "FormFileUploader",
    components: {Modal, FormFileUpload, AttachmentImageEditor},
    emits: ['input', 'update:modelValue', 'queue-change'],
    props: ['parent', 'uploadRoute', 'title', 'cropperCanvas', 'cropperStencil', 'width', 'height', 'presets',
        'chunkUploadRoute', 'chunkTarget', 'chunkThreshold', 'chunkSize', 'noSource',
        'noCamera', 'noUrl', 'noPixabay', 'noDescription', 'noInbox', 'noSourceTabs', 'cut', 'helpText'],
    data() {
        let canvasSettings = this.cropperCanvas || {};
        let stencilSettings = this.cropperStencil || {};
        if (this.width) {
            canvasSettings['width'] = this.width;
        }
        if (this.height) {
            canvasSettings['height'] = this.height;
        }
        if (this.width && this.height) {
            stencilSettings['aspectRatio'] = this.width / this.height;
        }
        return {
            dragging: false,
            queue: [],
            modalCropperOpen: false,
            editingItem: null,
            cropableImage: '',
            cropableAttachment: null,
            canvasSettings,
            stencilSettings,
            info: null,
            updateUploader: 0,
        }
    },
    computed: {
        isEditingImage() {
            return Boolean(this.editingItem?.file?.type?.startsWith('image/'))
        },
        uploading() {
            return this.queue.some(item => item.status === 'uploading')
        },
        readyItems() {
            return this.queue.filter(item => item.status === 'ready' || item.status === 'error')
        },
        totalProgress() {
            if (!this.queue.length) return 0
            return Math.round(this.queue.reduce((total, item) => total + item.progress, 0) / this.queue.length)
        },
    },
    methods: {
        /**
         * Add files to the local queue without starting an HTTP request.
         *
         * @param {File|File[]} files Files selected by the user.
         * @param {Object} metadata Initial target metadata for the queue items.
         * @returns {Object[]}
         */
        addFile(files, metadata = {}) {
            const selected = Array.isArray(files) ? files : [files]
            const newItems = appendFiles([], selected)
            newItems.forEach(item => {
                item.previewUrl = this.previewUrl(item.file)
                item.status = 'editing'
                if (this.title) item.title = this.title
                item.preset = metadata.preset || null
                item.cut = metadata.preset?.cut || metadata.cut || null
            })
            this.queue.push(...newItems)
            this.$emit('queue-change', this.queue.length)
            this.openNextEditor()
            return newItems
        },
        previewUrl(file) {
            return file.type?.startsWith('image/') && window.URL?.createObjectURL
                ? window.URL.createObjectURL(file)
                : null
        },
        isImage(item) {
            return item.file.type?.startsWith('image/') && item.previewUrl
        },
        statusLabel(item) {
            return {
                ready: 'Bereit',
                uploading: 'Wird hochgeladen',
                done: 'Fertig',
                error: 'Fehler',
            }[item.status] || item.status
        },
        dragEnter() {
            this.dragging = true
        },
        dragLeave() {
            this.dragging = false
        },
        drop(event) {
            this.dragging = false
            if (event.dataTransfer?.files?.length) this.addFile(Array.from(event.dataTransfer.files))
        },
        remove(item) {
            if (item.previewUrl && window.URL?.revokeObjectURL) window.URL.revokeObjectURL(item.previewUrl)
            this.queue = this.queue.filter(queueItem => queueItem.id !== item.id)
            this.$emit('queue-change', this.queue.length)
        },
        openEditor(item) {
            this.editingItem = item
        },
        /** Open the next file that is waiting for its upload details. */
        openNextEditor() {
            if (!this.editingItem) this.editingItem = this.queue.find(item => item.status === 'editing') || null
        },
        /** Discard the current pending upload when the dialog is cancelled. */
        cancelEditing() {
            if (this.editingItem?.status === 'editing') this.remove(this.editingItem)
            this.editingItem = null
            this.openNextEditor()
        },
        /** Finish the metadata dialog for a non-image file. */
        applyFileDetails() {
            if (!this.editingItem) return
            this.editingItem.status = 'ready'
            this.editingItem.error = null
            this.editingItem.progress = 0
            this.editingItem = null
            this.openNextEditor()
        },
        duplicate(item, metadata = {}) {
            const duplicate = duplicateUploadItem(item, metadata)
            duplicate.previewUrl = this.previewUrl(duplicate.file)
            this.queue.push(duplicate)
        },
        /**
         * Assign a configured target preset to a queue variant.
         *
         * @param {Object} item Queue variant.
         * @param {string} cut Target cut identifier.
         * @returns {void}
         */
        selectPreset(item, cut) {
            const preset = (this.presets || []).find(candidate => candidate.cut === cut) || null
            item.cut = preset?.cut || null
            item.preset = preset
        },
        applyEditorResult(file, transform, preset = null, variants = []) {
            if (!this.editingItem) return
            const sourceItem = this.editingItem
            const renderedVariants = variants.length ? variants : [{file, transform, preset}]
            renderedVariants.forEach((variant, index) => {
                const item = index === 0 ? sourceItem : duplicateUploadItem(sourceItem, {
                    file: variant.file,
                    preset: variant.preset,
                    cut: variant.preset?.cut || null,
                    transform: variant.transform,
                })
                if (index > 0) {
                    item.previewUrl = this.previewUrl(item.file)
                    this.queue.push(item)
                }
                if (item.previewUrl && index === 0 && window.URL?.revokeObjectURL) window.URL.revokeObjectURL(item.previewUrl)
                item.file = variant.file
                item.previewUrl = this.previewUrl(variant.file)
                item.transform = variant.transform
                item.preset = variant.preset
                item.cut = variant.preset?.cut || null
                item.status = 'ready'
                item.progress = 0
                item.error = null
            })
            this.editingItem = null
            this.$emit('queue-change', this.queue.length)
            this.openNextEditor()
        },
        retry(item) {
            item.status = 'ready'
            item.error = null
            item.progress = 0
            this.uploadItem(item)
        },
        async uploadAll() {
            for (const item of this.readyItems) await this.uploadItem(item)
        },
        async uploadItem(item) {
            item.status = 'uploading'
            item.error = null
            try {
                const responseData = this.shouldUseChunks(item)
                    ? await this.uploadChunked(item)
                    : await this.uploadDirect(item)
                item.progress = 100
                item.status = 'done'
                this.$emit('input', Array.isArray(responseData) ? responseData : [responseData])
                this.$emit('update:modelValue', Array.isArray(responseData) ? responseData : [responseData])
                if (item.previewUrl && window.URL?.revokeObjectURL) window.URL.revokeObjectURL(item.previewUrl)
                this.queue = this.queue.filter(queueItem => queueItem.id !== item.id)
                this.$emit('queue-change', this.queue.length)
            } catch (error) {
                item.status = 'error'
                item.error = error.response?.data?.message || error.message || 'Die Datei konnte nicht hochgeladen werden.'
            }
        },
        /** Determine whether a queue item uses the resumable protocol. */
        shouldUseChunks(item) {
            return Boolean(this.chunkUploadRoute && item.file.size >= (this.chunkThreshold || 10 * 1024 * 1024))
        },
        async uploadDirect(item) {
                const form = new FormData()
                form.append('attachment_text[0]', item.title)
                form.append('attachments[0]', item.file)
                if (item.cut || this.cut) form.append('cut', item.cut || this.cut)
                const response = await axios.post(this.uploadRoute, form, {
                    headers: {'Content-Type': 'multipart/form-data'},
                    onUploadProgress: progressEvent => {
                        if (progressEvent.total) item.progress = Math.round(progressEvent.loaded / progressEvent.total * 100)
                    },
                })
                return response.data
        },
        async uploadChunked(item) {
            const target = this.chunkTarget || {}
            return uploadFileInChunks(item.file, {
                chunkSize: this.chunkSize || 5 * 1024 * 1024,
                session: item.uploadSession,
                start: metadata => axios.post(this.chunkUploadRoute, {
                    ...metadata,
                    ...target,
                    title: item.title,
                    cut: item.cut || this.cut || null,
                }).then(response => response.data),
                status: session => axios.get(
                    route('api.attachment-uploads.status', {session})
                ).then(response => response.data),
                onSession: session => { item.uploadSession = session },
                onChunk: (index, received) => { item.uploadReceived = received },
                checksum: async blob => {
                    const buffer = await blob.arrayBuffer()
                    const digest = await crypto.subtle.digest('SHA-256', buffer)
                    return Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('')
                },
                sendChunk: (session, index, blob, checksum) => axios.put(
                    route('api.attachment-uploads.chunk', {session, index}),
                    blob,
                    {headers: {'Content-Type': 'application/octet-stream', 'X-Chunk-Checksum': checksum}}
                ).then(response => response.data),
                complete: session => axios.post(
                    route('api.attachment-uploads.complete', {session})
                ).then(response => response.data),
                onProgress: progress => { item.progress = progress },
            })
        },
        /** Download a URL source, using the authenticated client for internal proxies. */
        uploadUrl(data) {
            this.info = data.info;
            const download = data.pixabay
                ? this.$api().post(route('api.pixabay.image'), {url: data.url}, {responseType: 'blob'}).then(response => response.data)
                : data.authenticated
                ? this.$api().get(data.url, {responseType: 'blob'}).then(response => response.data)
                : fetch(data.url).then(response => response.blob())
            download.then(blob => {
                const name = data.url.split('/').pop().split('?')[0] || 'download'
                const [item] = this.addFile(new File([blob], name, {type: blob.type || 'application/octet-stream'}))
                if (data.title && item) item.title = data.title
            }).catch(() => {
                const [item] = this.addFile(new File([], 'URL-Datei'))
                item.status = 'error'
                item.error = 'Die Datei konnte von der URL nicht geladen werden.'
                this.editingItem = null
            })
        },
        uploadInbox(inboxFile) {
            axios.get(route('api.inbox.download', {filename: inboxFile.name}), {responseType: 'blob'})
                .then(response => {
                    const blob = response.data
                    const [item] = this.addFile(new File([blob], inboxFile.name, {
                        type: blob.type || 'application/octet-stream',
                    }))
                    if (item) item.title = inboxFile.name
                })
                .catch(() => {
                    const [item] = this.addFile(new File([], inboxFile.name))
                    item.status = 'error'
                    item.error = 'Die Datei aus der Ablage konnte nicht geladen werden.'
                })
        },
        /** Queue an existing attachment as a new local editing source. */
        async reuseAttachment(attachment, preset = null) {
            const response = await fetch(route('image', {path: attachment.file.replace('attachments/', '')}))
            const blob = await response.blob()
            const [item] = this.addFile(new File([blob], attachment.file.split('/').pop() || 'bild', {type: blob.type || 'image/jpeg'}), {preset})
            if (item) {
                item.title = attachment.title || item.title
            }
        },
    }
}
</script>

<style scoped>
.form-file-uploader {
    width: 100%;
}

.upload-drop-surface {
    padding: .75rem;
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: var(--bs-border-radius, .375rem);
    background: var(--bs-body-bg, #f8f9fa);
    transition: border-color .15s ease, background-color .15s ease;
}

.upload-drop-surface.dragging {
    border-color: var(--bs-primary, #0d6efd);
    background: rgba(var(--bs-primary-rgb), .04);
}

.upload-summary {
    margin-bottom: .75rem;
    color: var(--bs-secondary-color, #6c757d);
    font-size: .9rem;
}

.upload-queue {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    margin-top: 1rem;
}

.upload-queue-item {
    display: grid;
    grid-template-columns: 5.5rem minmax(0, 1fr) auto auto auto;
    gap: .75rem;
    align-items: center;
    padding: .75rem;
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: var(--bs-border-radius, .375rem);
    background: var(--bs-body-bg, #fff);
    box-shadow: var(--bs-box-shadow-sm, 0 .125rem .25rem rgba(0, 0, 0, .075));
}

.queue-preview {
    display: block;
    width: 5.5rem;
    height: 4.25rem;
    padding: .25rem;
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: .5rem;
    background-color: #f8f9fa;
    background-image: linear-gradient(45deg, #e1e5e9 25%, transparent 25%), linear-gradient(-45deg, #e1e5e9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e1e5e9 75%), linear-gradient(-45deg, transparent 75%, #e1e5e9 75%);
    background-size: .75rem .75rem;
    background-position: 0 0, 0 .375rem, .375rem -.375rem, -.375rem 0;
    object-fit: contain;
}

.queue-file-icon {
    display: grid;
    width: 5.5rem;
    height: 4.25rem;
    place-items: center;
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: .5rem;
    color: var(--bs-secondary-color, #6c757d);
    background: var(--bs-light, #f8f9fa);
    font-size: 1.75rem;
}

.queue-details {
    min-width: 0;
}

.queue-title {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.queue-target {
    display: inline-block;
    margin: .25rem 0;
    font-weight: 400;
}

.queue-details small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.upload-queue-item > .btn {
    min-width: 2.5rem;
    min-height: 2.5rem;
}

.upload-queue-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
    padding-top: .25rem;
}

.edit-file-meta {
    margin-bottom: 1rem;
}

.generic-file-editor {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-light-bg-subtle);
}

.file-preview-placeholder {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: var(--bs-secondary-color);
}

.file-preview-placeholder .mdi {
    font-size: 2rem;
}

@media (max-width: 767.98px) {
    .upload-queue-item {
        grid-template-columns: 4.5rem minmax(0, 1fr);
    }

    .queue-preview,
    .queue-file-icon {
        width: 4.5rem;
        height: 3.5rem;
    }

    .upload-queue-item > .btn {
        grid-row: 2;
    }
}

</style>

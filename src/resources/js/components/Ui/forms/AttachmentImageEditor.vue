<template>
    <div class="attachment-image-editor">
        <div class="editor-layout">
            <aside
                v-if="presets.length"
                class="target-panel"
            >
                <div class="target-panel-heading">
                    <div class="fw-semibold">Ziele</div>
                    <small class="text-muted">Ein Bild kann mehrere Zuschnitte erhalten.</small>
                </div>
                <label
                    v-for="candidate in presets"
                    :key="candidate.cut"
                    class="target-option"
                    :class="{ active: selectedTargets.includes(candidate.cut) }"
                >
                    <input
                        v-model="selectedTargets"
                        type="checkbox"
                        :value="candidate.cut"
                        @change="selectTarget(candidate.cut)"
                    />
                    <span
                        ><strong>{{ candidate.title }}</strong
                        ><small
                            >{{ candidate.width }}×{{ candidate.height }} ·
                            {{ formatAspectRatio(candidate) }}</small
                        ></span
                    >
                </label>
                <label
                    v-if="allowFreeCrop"
                    class="target-option"
                    :class="{ active: isFreeCropSelected }"
                >
                    <input
                        v-model="isFreeCropSelected"
                        type="checkbox"
                        @change="selectFreeCrop"
                    />
                    <span
                        ><strong>Freier Zuschnitt</strong
                        ><small>Originalformat ohne feste Zielgröße</small></span
                    >
                </label>
            </aside>
            <section class="editor-main">
                <div class="editor-stage-wrap">
                    <div
                        class="editor-stage"
                        :style="stageStyle"
                        @wheel.prevent="handleWheel"
                        @pointerdown="startDrag"
                        @pointermove="moveDrag"
                        @pointerup="stopDrag"
                        @pointercancel="stopDrag"
                    >
                        <img
                            v-if="imageReady"
                            ref="image"
                            :src="previewUrl"
                            alt=""
                            class="editor-image"
                            :style="imageStyle"
                            draggable="false"
                        />
                        <div
                            v-else
                            class="editor-loading"
                        >
                            Bild wird vorbereitet …
                        </div>
                    </div>
                </div>
                <div class="editor-caption">
                    <div>
                        <strong>{{ currentTargetLabel }}</strong
                        ><small>{{ currentTargetMeta }}</small>
                    </div>
                    <span
                        v-if="targetCount > 1"
                        class="text-muted"
                        >{{ targetIndex + 1 }} / {{ targetCount }}</span
                    >
                </div>
                <div
                    v-if="targetCount > 1"
                    class="target-tabs"
                    role="tablist"
                    aria-label="Zielzuschnitte"
                >
                    <button
                        v-for="target in activeTargets"
                        :key="target.key"
                        type="button"
                        class="btn btn-sm"
                        :class="
                            target.key === activeTargetKey ? 'btn-primary' : 'btn-outline-secondary'
                        "
                        @click="activeTargetKey = target.key"
                    >
                        {{ target.label }}
                    </button>
                </div>
            </section>
        </div>
        <div
            class="editor-toolbar"
            role="toolbar"
            aria-label="Bildbearbeitung"
        >
            <button
                type="button"
                class="btn btn-outline-secondary"
                title="90° nach links drehen"
                @click="rotate(-90)"
            >
                <span
                    class="mdi mdi-rotate-left"
                    aria-hidden="true"
                ></span
                ><span class="visually-hidden">90° links</span>
            </button>
            <button
                type="button"
                class="btn btn-outline-secondary"
                title="90° nach rechts drehen"
                @click="rotate(90)"
            >
                <span
                    class="mdi mdi-rotate-right"
                    aria-hidden="true"
                ></span
                ><span class="visually-hidden">90° rechts</span>
            </button>
            <button
                type="button"
                class="btn btn-outline-secondary"
                title="Horizontal spiegeln"
                @click="flip('x')"
            >
                <span
                    class="mdi mdi-flip-horizontal"
                    aria-hidden="true"
                ></span
                ><span class="visually-hidden">Horizontal spiegeln</span>
            </button>
            <button
                type="button"
                class="btn btn-outline-secondary"
                title="Vertikal spiegeln"
                @click="flip('y')"
            >
                <span
                    class="mdi mdi-flip-vertical"
                    aria-hidden="true"
                ></span
                ><span class="visually-hidden">Vertikal spiegeln</span>
            </button>
            <label class="editor-control"
                ><span>Zoom</span
                ><input
                    v-model.number="currentState.zoom"
                    type="range"
                    min=".25"
                    max="8"
                    step=".01"
                    aria-label="Zoom"
                /><output>{{ currentState.zoom.toFixed(2) }}×</output></label
            >
            <label class="editor-control"
                ><span>Drehung</span
                ><input
                    v-model.number="currentState.rotation"
                    type="range"
                    min="-180"
                    max="180"
                    step="1"
                    aria-label="Drehung"
                /><output>{{ currentState.rotation }}°</output></label
            >
            <button
                type="button"
                class="btn btn-outline-secondary"
                @click="resetTransform"
            >
                Zurücksetzen
            </button>
            <div class="editor-actions">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="$emit('cancel')"
                >
                    Abbrechen
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="!canApply"
                    @click="applyChanges"
                >
                    Übernehmen
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AttachmentImageEditor',
    emits: ['apply', 'cancel'],
    props: {
        file: { type: File, required: true },
        width: { type: Number, default: null },
        height: { type: Number, default: null },
        preset: { type: Object, default: null },
        presets: { type: Array, default: () => [] },
        initialTransform: { type: Object, default: null },
        allowFreeCrop: { type: Boolean, default: true },
    },
    data() {
        const initialKey = this.preset?.cut || 'free'
        return {
            previewUrl:
                typeof URL.createObjectURL === 'function' ? URL.createObjectURL(this.file) : '',
            imageReady: false,
            sourceImage: null,
            selectedTargets: this.preset?.cut ? [this.preset.cut] : [],
            isFreeCropSelected: this.allowFreeCrop && !this.preset?.cut && !this.presets.length,
            activeTargetKey: initialKey,
            targetStates: { [initialKey]: this.createState(this.initialTransform) },
            dragState: null,
        }
    },
    computed: {
        activeTargets() {
            const targets = this.selectedTargets
                .map((cut) => {
                    const preset = this.presets.find((candidate) => candidate.cut === cut)
                    return preset ? { key: cut, label: preset.title, preset } : null
                })
                .filter(Boolean)
            if (this.allowFreeCrop && this.isFreeCropSelected)
                targets.push({ key: 'free', label: 'Freier Zuschnitt', preset: null })
            return targets
        },
        targetCount() {
            return this.activeTargets.length
        },
        targetIndex() {
            return Math.max(
                0,
                this.activeTargets.findIndex((target) => target.key === this.activeTargetKey),
            )
        },
        currentTarget() {
            return (
                this.activeTargets.find((target) => target.key === this.activeTargetKey) ||
                this.activeTargets[0] || { key: 'free', label: 'Freier Zuschnitt', preset: null }
            )
        },
        currentState() {
            return this.stateFor(this.currentTarget.key)
        },
        currentTargetLabel() {
            return this.currentTarget.label
        },
        currentTargetMeta() {
            return this.currentTarget.preset
                ? `Zielgröße ${this.currentTarget.preset.width} × ${this.currentTarget.preset.height} Pixel`
                : 'Bild ziehen und frei zuschneiden'
        },
        stageStyle() {
            const targetWidth = this.currentTarget.preset?.width || this.width || 16
            const targetHeight = this.currentTarget.preset?.height || this.height || 9
            const aspectRatio = targetWidth / targetHeight
            const wrapper = this.$el?.querySelector('.editor-stage-wrap')
            const availableWidth = Math.max(320, (wrapper?.clientWidth || 672) - 32)
            const availableHeight = Math.max(260, Math.round(window.innerHeight * 0.62))
            const stageWidth = Math.min(availableWidth, Math.round(availableHeight * aspectRatio))
            const stageHeight = Math.round(stageWidth / aspectRatio)
            return {
                width: `${stageWidth}px`,
                height: `${stageHeight}px`,
                aspectRatio: `${targetWidth} / ${targetHeight}`,
            }
        },
        imageStyle() {
            const state = this.currentState
            const stage = this.$el?.querySelector('.editor-stage')
            const stageWidth = stage?.clientWidth || 640
            const stageHeight =
                (stageWidth * (this.currentTarget.preset?.height || this.height || 9)) /
                (this.currentTarget.preset?.width || this.width || 16)
            const imageWidth = this.sourceImage?.naturalWidth || this.width || 1200
            const imageHeight = this.sourceImage?.naturalHeight || this.height || 800
            const scale = this.coverScale(
                imageWidth,
                imageHeight,
                stageWidth,
                stageHeight,
                state.rotation,
            )
            return {
                width: `${imageWidth * scale}px`,
                height: `${imageHeight * scale}px`,
                transform: `translate(calc(-50% + ${state.offsetX}px), calc(-50% + ${state.offsetY}px)) rotate(${state.rotation}deg) scale(${state.zoom}) scaleX(${state.flipX ? -1 : 1}) scaleY(${state.flipY ? -1 : 1})`,
            }
        },
        canApply() {
            return this.imageReady && (this.activeTargets.length > 0 || !this.presets.length)
        },
    },
    mounted() {
        if (!this.previewUrl) {
            this.imageReady = true
            return
        }
        const image = new Image()
        image.onload = () => {
            this.sourceImage = image
            this.imageReady = true
        }
        image.onerror = () => {
            this.imageReady = false
        }
        image.src = this.previewUrl
    },
    beforeUnmount() {
        if (this.previewUrl && typeof URL.revokeObjectURL === 'function')
            URL.revokeObjectURL(this.previewUrl)
    },
    methods: {
        /** Create the mutable transform state for one target. */
        createState(transform = null) {
            return {
                offsetX: transform?.offsetX || 0,
                offsetY: transform?.offsetY || 0,
                zoom: transform?.zoom || 1,
                rotation: transform?.rotation || 0,
                flipX: transform?.flipX || false,
                flipY: transform?.flipY || false,
            }
        },
        /** Return and lazily create a transform state for a target. */
        stateFor(key) {
            if (!this.targetStates[key]) this.targetStates[key] = this.createState()
            return this.targetStates[key]
        },
        /** Switch the active target after selecting it. */
        selectTarget(cut) {
            if (this.selectedTargets.includes(cut)) this.activeTargetKey = cut
            else if (this.activeTargetKey === cut)
                this.activeTargetKey =
                    this.selectedTargets[0] || (this.isFreeCropSelected ? 'free' : '')
        },
        /** Switch the optional free-crop target. */
        selectFreeCrop() {
            if (this.isFreeCropSelected) this.activeTargetKey = 'free'
            else if (this.activeTargetKey === 'free')
                this.activeTargetKey = this.selectedTargets[0] || ''
        },
        /** Rotate the active target by an arbitrary angle. */
        rotate(degrees) {
            this.currentState.rotation = this.normalizeRotation(
                this.currentState.rotation + degrees,
            )
        },
        /** Toggle one axis without losing the other transforms. */
        flip(axis) {
            const property = axis === 'x' ? 'flipX' : 'flipY'
            this.currentState[property] = !this.currentState[property]
        },
        /** Reset the active target transform. */
        resetTransform() {
            this.targetStates[this.currentTarget.key] = this.createState()
        },
        /** Begin dragging the active image. */
        startDrag(event) {
            if (!this.imageReady) return
            this.dragState = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
            }
            event.currentTarget.setPointerCapture?.(event.pointerId)
        },
        /** Move the active image while dragging. */
        moveDrag(event) {
            if (!this.dragState || this.dragState.pointerId !== event.pointerId) return
            this.currentState.offsetX += event.clientX - this.dragState.startX
            this.currentState.offsetY += event.clientY - this.dragState.startY
            this.dragState = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
            }
        },
        /** End dragging the active image. */
        stopDrag(event) {
            if (this.dragState?.pointerId !== event.pointerId) return
            event.currentTarget.releasePointerCapture?.(event.pointerId)
            this.dragState = null
        },
        /** Zoom the active image with Ctrl plus the mouse wheel. */
        handleWheel(event) {
            if (!event.ctrlKey) return
            this.currentState.zoom = Math.max(
                0.25,
                Math.min(8, this.currentState.zoom + (event.deltaY < 0 ? 0.1 : -0.1)),
            )
        },
        /** Normalize rotations to the slider range. */
        normalizeRotation(rotation) {
            let normalized = rotation
            while (normalized > 180) normalized -= 360
            while (normalized < -180) normalized += 360
            return normalized
        },
        /** Format a target aspect ratio. */
        formatAspectRatio(candidate) {
            return candidate?.width && candidate?.height
                ? `${(candidate.width / candidate.height).toFixed(2)}:1`
                : 'frei'
        },
        /** Calculate a cover scale including the rotated image bounds. */
        coverScale(imageWidth, imageHeight, stageWidth, stageHeight, rotation) {
            const radians = (Math.abs(rotation) * Math.PI) / 180
            const rotatedWidth =
                Math.abs(imageWidth * Math.cos(radians)) + Math.abs(imageHeight * Math.sin(radians))
            const rotatedHeight =
                Math.abs(imageWidth * Math.sin(radians)) + Math.abs(imageHeight * Math.cos(radians))
            return Math.max(stageWidth / rotatedWidth, stageHeight / rotatedHeight)
        },
        /** Render a target state into a local file. */
        renderVariant(target) {
            const image = this.sourceImage || this.$refs.image
            if (!image || !this.imageReady)
                return Promise.reject(new Error('Bildzuschnitt ist nicht verfügbar.'))
            const width = target.preset?.width || image.naturalWidth || this.width || 1200
            const height = target.preset?.height || image.naturalHeight || this.height || 800
            const stageWidth = this.$el.querySelector('.editor-stage')?.clientWidth || 640
            const stageHeight = (stageWidth * height) / width
            const imageWidth = image.naturalWidth || width
            const imageHeight = image.naturalHeight || height
            const state = this.stateFor(target.key)
            const baseScale = this.coverScale(
                imageWidth,
                imageHeight,
                stageWidth,
                stageHeight,
                state.rotation,
            )
            const outputScale = width / stageWidth
            const canvas = document.createElement('canvas')
            canvas.width = width
            canvas.height = height
            const context = canvas.getContext('2d')
            if (!context)
                return Promise.reject(new Error('Canvas-Kontext konnte nicht erstellt werden.'))
            const outputType = this.file.type === 'image/png' ? 'image/png' : 'image/jpeg'
            if (outputType === 'image/jpeg') {
                context.fillStyle = '#ffffff'
                context.fillRect(0, 0, width, height)
            }
            context.translate(
                width / 2 + state.offsetX * outputScale,
                height / 2 + state.offsetY * outputScale,
            )
            context.rotate((state.rotation * Math.PI) / 180)
            context.scale(state.flipX ? -1 : 1, state.flipY ? -1 : 1)
            context.drawImage(
                image,
                (-imageWidth * baseScale * state.zoom * outputScale) / 2,
                (-imageHeight * baseScale * state.zoom * outputScale) / 2,
                imageWidth * baseScale * state.zoom * outputScale,
                imageHeight * baseScale * state.zoom * outputScale,
            )
            return this.renderFile(canvas, target.key, outputType)
        },
        /** Turn a rendered canvas into a local file. */
        renderFile(canvas, key = '', type = 'image/jpeg') {
            return new Promise((resolve, reject) => {
                canvas.toBlob(
                    (blob) => {
                        if (!blob)
                            return reject(new Error('Das Bild konnte nicht verarbeitet werden.'))
                        const suffix = key && key !== 'free' ? `-${key}` : ''
                        const extension = type === 'image/png' ? 'png' : 'jpg'
                        resolve(
                            new File(
                                [blob],
                                `${this.file.name.replace(/\.[^.]+$/, '')}${suffix}.${extension}`,
                                { type: blob.type || type },
                            ),
                        )
                    },
                    type,
                    0.92,
                )
            })
        },
        /** Render every selected target as an independent upload variant. */
        async applyChanges() {
            const targets = this.activeTargets.length
                ? this.activeTargets
                : [{ key: 'free', label: 'Freier Zuschnitt', preset: null }]
            const variants = []
            for (const target of targets)
                variants.push({
                    file: await this.renderVariant(target),
                    transform: { ...this.stateFor(target.key) },
                    preset: target.preset,
                })
            const first = variants[0]
            this.$emit('apply', first.file, first.transform, first.preset, variants)
        },
    },
}
</script>

<style scoped>
.editor-layout {
    display: grid;
    grid-template-columns: minmax(12rem, 15rem) minmax(0, 1fr);
    gap: 1rem;
}
.target-panel,
.editor-main {
    min-width: 0;
}
.target-panel {
    padding: 0.75rem;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-light-bg-subtle, #f8f9fa);
}
.target-panel-heading {
    margin-bottom: 0.75rem;
}
.target-option {
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
    width: 100%;
    padding: 0.65rem;
    border: 1px solid transparent;
    border-radius: var(--bs-border-radius-sm);
    cursor: pointer;
}
.target-option:hover,
.target-option.active {
    border-color: var(--bs-primary-border-subtle);
    background: rgba(var(--bs-primary-rgb), 0.08);
}
.target-option span {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.target-option small {
    color: var(--bs-secondary-color);
}
.editor-stage-wrap {
    padding: 1rem;
    border: 1px dashed var(--bs-primary-border-subtle);
    border-radius: var(--bs-border-radius);
    background: var(--bs-light-bg-subtle, #f8f9fa);
}
.editor-stage {
    position: relative;
    width: min(100%, 42rem);
    max-height: 55vh;
    margin: 0 auto;
    overflow: hidden;
    border: 2px solid var(--bs-primary);
    border-radius: var(--bs-border-radius);
    background-color: #f8f9fa;
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
    cursor: grab;
}
.editor-stage:active {
    cursor: grabbing;
}
.editor-image {
    position: absolute;
    top: 50%;
    left: 50%;
    max-width: none;
    user-select: none;
    pointer-events: none;
}
.editor-loading {
    display: grid;
    min-height: 20rem;
    place-items: center;
    color: var(--bs-secondary-color);
}
.editor-caption {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 0.75rem;
}
.editor-caption small {
    display: block;
    color: var(--bs-secondary-color);
}
.target-tabs,
.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
}
.editor-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0.25rem;
}
.editor-control input {
    width: 9rem;
}
.editor-control output {
    min-width: 3.5rem;
    color: var(--bs-secondary-color);
    font-variant-numeric: tabular-nums;
}
@media (max-width: 767.98px) {
    .editor-layout {
        grid-template-columns: 1fr;
    }
    .target-panel {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
        gap: 0.25rem;
    }
    .target-panel-heading {
        grid-column: 1/-1;
    }
    .editor-control {
        width: 100%;
        margin: 0.25rem 0;
    }
    .editor-control input {
        flex: 1;
    }
}
<style scoped > .editor-stage {
    width: auto;
    max-height: none;
}
.editor-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}
@media (max-width: 767.98px) {
    .editor-actions {
        width: 100%;
        margin-left: 0;
        justify-content: flex-end;
    }
}
</style>

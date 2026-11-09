<template>
    <div
        class="attachment-lightbox"
        tabindex="-1"
        @click.self="close"
        @keydown.esc="close"
        @keydown.left.prevent="previous"
        @keydown.right.prevent="next"
    >
        <button
            type="button"
            class="lightbox-close btn btn-dark"
            aria-label="Vorschau schließen"
            @click="close"
        >
            <span
                class="mdi mdi-close"
                aria-hidden="true"
            ></span>
        </button>
        <button
            v-if="attachments.length > 1"
            type="button"
            class="lightbox-nav lightbox-prev btn btn-dark"
            aria-label="Vorheriges Bild"
            @click="previous"
        >
            <span
                class="mdi mdi-chevron-left"
                aria-hidden="true"
            ></span>
        </button>
        <figure class="lightbox-content">
            <div
                class="lightbox-image-stage"
                @wheel.prevent="handleWheel"
            >
                <img
                    class="img-fluid"
                    :style="{ transform: `scale(${zoom})` }"
                    :src="imageRoute(current)"
                    :alt="current.title"
                />
            </div>
            <figcaption>
                <strong>{{ current.title }}</strong>
                <span class="lightbox-counter">{{ index + 1 }} / {{ attachments.length }}</span>
                <span
                    class="lightbox-controls"
                    aria-label="Bildzoom"
                >
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-light"
                        aria-label="Verkleinern"
                        @click.stop="changeZoom(-0.25)"
                    >
                        −
                    </button>
                    <span>{{ Math.round(zoom * 100) }}%</span>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-light"
                        aria-label="Vergrößern"
                        @click.stop="changeZoom(0.25)"
                    >
                        +
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-light"
                        @click.stop="zoom = 1"
                    >
                        100%
                    </button>
                </span>
                <a
                    class="btn btn-primary btn-sm ms-2"
                    :href="imageRoute(current, true)"
                >
                    <span
                        class="mdi mdi-download"
                        aria-hidden="true"
                    ></span>
                    Download
                </a>
                <a
                    class="btn btn-secondary btn-sm ms-2"
                    target="_blank"
                    :href="imageRoute(current)"
                    >In neuem Tab öffnen</a
                >
            </figcaption>
        </figure>
        <button
            v-if="attachments.length > 1"
            type="button"
            class="lightbox-nav lightbox-next btn btn-dark"
            aria-label="Nächstes Bild"
            @click="next"
        >
            <span
                class="mdi mdi-chevron-right"
                aria-hidden="true"
            ></span>
        </button>
    </div>
</template>

<script>
export default {
    name: 'AttachmentLightbox',
    emits: ['close', 'previous', 'next', 'download'],
    props: {
        attachments: { type: Array, default: () => [] },
        index: { type: Number, default: 0 },
    },
    data() {
        return { zoom: 1 }
    },
    watch: {
        index() {
            this.zoom = 1
        },
    },
    computed: {
        current() {
            return this.attachments[this.index] || this.attachments[0]
        },
    },
    mounted() {
        this.$el.focus()
    },
    methods: {
        close() {
            this.$emit('close')
        },
        previous() {
            this.$emit('previous')
        },
        next() {
            this.$emit('next')
        },
        /** Change the lightbox zoom while keeping it within a usable range. */
        changeZoom(delta) {
            this.zoom = Math.max(0.25, Math.min(4, this.zoom + delta))
        },
        /** Zoom only when the user explicitly holds Ctrl while scrolling. */
        handleWheel(event) {
            if (event.ctrlKey) this.changeZoom(event.deltaY < 0 ? 0.25 : -0.25)
        },
        imageRoute(attachment, download = false) {
            return route('image', {
                path: attachment.file.replace('attachments/', ''),
                ...(download ? { download: 1 } : {}),
            })
        },
    },
}
</script>

<style scoped>
.attachment-lightbox {
    position: fixed;
    inset: 0;
    z-index: 20000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem 5rem 2rem;
    background: rgba(0, 0, 0, 0.82);
    outline: none;
}

.lightbox-content {
    max-width: min(92vw, 1100px);
    max-height: 90vh;
    margin: 0;
    text-align: center;
    color: white;
}

.lightbox-content img {
    max-height: 78vh;
    object-fit: contain;
    transform-origin: center;
    transition: transform 0.15s ease;
}

.lightbox-image-stage {
    max-width: min(92vw, 1100px);
    max-height: 78vh;
    overflow: auto;
    padding: 1rem;
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
}

.lightbox-content figcaption {
    padding-top: 0.75rem;
}

.lightbox-counter {
    display: inline-block;
    margin-left: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-variant-numeric: tabular-nums;
}

.lightbox-controls {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-left: 0.75rem;
}

.lightbox-close,
.lightbox-nav {
    position: absolute;
    min-width: 2.75rem;
    min-height: 2.75rem;
    border-radius: 50%;
    opacity: 0.9;
    z-index: 2;
}

.lightbox-close {
    top: 1rem;
    right: 1rem;
}
.lightbox-prev {
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
}
.lightbox-next {
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
}

@media (max-width: 576px) {
    .attachment-lightbox {
        padding: 4rem 1rem 1rem;
    }
    .lightbox-prev {
        left: 0.25rem;
    }
    .lightbox-next {
        right: 0.25rem;
    }
    .lightbox-content figcaption a {
        margin-top: 0.5rem;
    }
}
</style>

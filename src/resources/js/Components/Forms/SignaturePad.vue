<template>
    <div class="signature-pad">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label
                class="form-label mb-0"
                :for="canvasId"
                >{{ label }}</label
            >
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                data-testid="clear-signature"
                @click="clear"
            >
                Unterschrift löschen
            </button>
        </div>
        <div class="signature-pad__canvas border rounded bg-white">
            <canvas
                :id="canvasId"
                ref="canvas"
                :aria-label="`Unterschrift von ${label}`"
                @pointerdown="handlePointerDown"
                @pointermove="handlePointerMove"
                @pointerup="handlePointerUp"
            ></canvas>
        </div>
        <small
            class="text-muted"
            data-testid="signature-state"
            >{{
                isEmpty
                    ? required
                        ? 'Unterschrift erforderlich'
                        : 'Noch nicht unterschrieben'
                    : 'Unterschrift erfasst'
            }}</small
        >
    </div>
</template>

<script>
let nextId = 0

/**
 * Capture one person's electronic signature as a PNG image.
 */
export default {
    name: 'SignaturePadField',
    props: {
        label: { type: String, required: true },
        modelValue: { type: String, default: '' },
        required: { type: Boolean, default: false },
    },
    emits: ['update:modelValue', 'changed'],
    data() {
        return {
            canvasId: `signature-pad-${nextId++}`,
            context: null,
            drawing: false,
            isEmpty: !this.modelValue,
            lastPoint: null,
            strokes: [],
        }
    },
    mounted() {
        this.context = this.$refs.canvas.getContext('2d')
        this.resizeCanvas()
        window.addEventListener('resize', this.resizeCanvas)
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.resizeCanvas)
    },
    methods: {
        /**
         * Keep the canvas density-independent while preserving drawn points.
         *
         * @returns {void}
         */
        resizeCanvas() {
            if (!this.context || !this.$refs.canvas) return
            const ratio = Math.max(window.devicePixelRatio || 1, 1)
            const canvas = this.$refs.canvas
            canvas.width = Math.max(canvas.offsetWidth, 300) * ratio
            canvas.height = Math.max(canvas.offsetHeight, 144) * ratio
            this.context.setTransform?.(ratio, 0, 0, ratio, 0, 0)
            this.redraw()
        },
        /**
         * Prevent browser scrolling while starting a signature stroke.
         *
         * @param {PointerEvent} event Pointer event from the canvas.
         * @returns {void}
         */
        handlePointerDown(event) {
            event.currentTarget.setPointerCapture?.(event.pointerId)
            this.drawing = true
            this.lastPoint = this.pointFromEvent(event)
            this.strokes.push([this.lastPoint])
        },
        /**
         * Draw one pointer segment and retain it for orientation changes.
         *
         * @param {PointerEvent} event Pointer event from the canvas.
         * @returns {void}
         */
        handlePointerMove(event) {
            if (!this.drawing) return
            const point = this.pointFromEvent(event)
            this.context.beginPath()
            this.context.moveTo(this.lastPoint.x, this.lastPoint.y)
            this.context.lineTo(point.x, point.y)
            this.context.stroke()
            this.strokes.at(-1).push(point)
            this.lastPoint = point
        },
        /**
         * Finish the current pointer stroke and publish its PNG image.
         *
         * @returns {void}
         */
        handlePointerUp() {
            this.drawing = false
            this.lastPoint = null
            this.emitSignature()
        },
        /**
         * Convert viewport coordinates into canvas coordinates.
         *
         * @param {PointerEvent} event Pointer event.
         * @returns {{x: number, y: number}} Canvas coordinates.
         */
        pointFromEvent(event) {
            const bounds = this.$refs.canvas.getBoundingClientRect()
            return { x: event.clientX - bounds.left, y: event.clientY - bounds.top }
        },
        /**
         * Redraw all retained strokes after a canvas resize.
         *
         * @returns {void}
         */
        redraw() {
            this.context.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height)
            this.context.lineWidth = 1.6
            this.context.lineCap = 'round'
            this.context.strokeStyle = '#111'
            this.strokes.forEach((stroke) => {
                if (stroke.length < 2) return
                this.context.beginPath()
                this.context.moveTo(stroke[0].x, stroke[0].y)
                stroke.slice(1).forEach((point) => this.context.lineTo(point.x, point.y))
                this.context.stroke()
            })
        },
        /**
         * Emit the current signature image after a completed stroke.
         *
         * @returns {void}
         */
        emitSignature() {
            this.isEmpty = this.strokes.length === 0
            const image = this.isEmpty ? '' : this.$refs.canvas.toDataURL('image/png')
            this.$emit('update:modelValue', image)
            this.$emit('changed', image)
        },
        /**
         * Clear the current signature.
         *
         * @returns {void}
         */
        clear() {
            this.strokes = []
            this.context?.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height)
            this.isEmpty = true
            this.$emit('update:modelValue', '')
            this.$emit('changed', '')
        },
    },
}
</script>

<style scoped>
.signature-pad__canvas {
    height: 9rem;
    touch-action: none;
}

.signature-pad__canvas canvas {
    display: block;
    height: 100%;
    width: 100%;
}
</style>

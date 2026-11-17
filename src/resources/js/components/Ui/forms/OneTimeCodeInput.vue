<template>
    <div>
        <div
            class="one-time-code-input"
            role="group"
            :aria-labelledby="labelId"
            @paste="pasteCode"
        >
            <span
                :id="labelId"
                class="form-label"
                >{{ label }}</span
            >
            <input
                v-for="(_, index) in digits"
                :key="index"
                :ref="(element) => setInputRef(element, index)"
                :data-testid="`${testIdPrefix}-digit-${index}`"
                :data-code-index="index"
                type="text"
                inputmode="numeric"
                :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                :aria-label="`Ziffer ${index + 1} von ${length}`"
                :value="digits[index]"
                maxlength="1"
                :autofocus="index === 0"
                required
                class="form-control code-digit"
                @input="updateDigit(index, $event.target.value)"
                @keydown="handleKeydown(index, $event)"
            />
        </div>
        <input
            type="hidden"
            :name="name"
            :value="code"
        />
        <span
            class="visually-hidden"
            aria-live="polite"
            >{{ announcement }}</span
        >
    </div>
</template>

<script>
/**
 * Accessible segmented numeric one-time-code field with paste and autofill support.
 * @property {string} modelValue Current concatenated code.
 * @property {number} length Number of code digits.
 * @property {string} name Form field name for the hidden combined value.
 */
export default {
    name: 'OneTimeCodeInput',
    props: {
        modelValue: { type: String, default: '' },
        length: { type: Number, default: 6 },
        label: { type: String, required: true },
        name: { type: String, default: 'code' },
        testIdPrefix: { type: String, default: 'one-time-code' },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            digits: Array.from({ length: this.length }, (_, index) => this.modelValue[index] || ''),
            inputs: [],
            announcement: '',
        }
    },
    computed: {
        /** Stable label target for the input group. */
        labelId() {
            return `${this.testIdPrefix}-label`
        },
        /** Return the code submitted to the server. */
        code() {
            return this.digits.join('')
        },
    },
    watch: {
        /** Resize visible fields when switching between six- and eight-digit codes. */
        length(value) {
            this.digits = Array.from({ length: value }, (_, index) => this.modelValue[index] || '')
        },
        /** Distribute browser one-time-code autofill across the fields. */
        modelValue(value) {
            const normalized = this.cleanCode(value).slice(0, this.length)
            if (normalized !== value) this.$emit('update:modelValue', normalized)
            if (normalized === this.code) return
            this.digits = Array.from({ length: this.length }, (_, index) => normalized[index] || '')
        },
    },
    methods: {
        /** Keep refs to native inputs for predictable keyboard navigation. */
        setInputRef(element, index) {
            if (element) this.inputs[index] = element
        },
        /** Remove whitespace and non-digits from authenticator codes. */
        cleanCode(value) {
            return String(value || '').replace(/\D/g, '')
        },
        /** Update one digit, distribute autofill, and move focus forward. */
        updateDigit(index, value) {
            const entered = this.cleanCode(value)
            const next = [...this.digits]
            if (entered.length > 1) {
                next.fill('')
                entered
                    .slice(0, this.length)
                    .split('')
                    .forEach((digit, offset) => {
                        next[offset] = digit
                    })
            } else {
                next[index] = entered
                if (entered && index < this.length - 1) this.inputs[index + 1]?.focus()
            }
            this.digits = next
            this.$emit('update:modelValue', next.join(''))
            this.announcement = `${next.filter(Boolean).length} von ${this.length} Ziffern eingegeben.`
        },
        /** Distribute a pasted numeric code from the first field. */
        pasteCode(event) {
            const pasted = this.cleanCode(event.clipboardData?.getData('text'))
            if (!pasted) return
            event.preventDefault()
            const normalized = pasted.slice(0, this.length)
            this.digits = Array.from({ length: this.length }, (_, index) => normalized[index] || '')
            this.$emit('update:modelValue', normalized)
            this.announcement =
                normalized.length === this.length
                    ? 'Sicherheitscode vollständig eingefügt.'
                    : `${normalized.length} von ${this.length} Ziffern eingefügt.`
            this.inputs[Math.min(pasted.length, this.length) - 1]?.focus()
        },
        /** Move backward on Backspace and support arrow-key navigation. */
        handleKeydown(index, event) {
            if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                this.inputs[index - 1]?.focus()
            } else if (event.key === 'ArrowLeft' && index > 0) {
                this.inputs[index - 1]?.focus()
            } else if (event.key === 'ArrowRight' && index < this.length - 1) {
                this.inputs[index + 1]?.focus()
            }
        },
    },
}
</script>

<style scoped>
.one-time-code-input {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.one-time-code-input .form-label {
    flex-basis: 100%;
}

.code-digit {
    width: 3rem;
    text-align: center;
    font-size: 1.25rem;
}
</style>

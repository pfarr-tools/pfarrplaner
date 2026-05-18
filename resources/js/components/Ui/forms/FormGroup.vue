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
    <div class="form-group form-field" :class="groupClasses">
        <value-check v-if="isCheckedItem" :value="value" />
        <label v-if="label" :for="fieldId" class="control-label form-label">
            <span v-if="preLabel" :class="preLabel" aria-hidden="true"></span>
            {{ label }}
            <span v-if="required" class="text-danger ms-1" aria-hidden="true">*</span>
            <span v-if="required" class="visually-hidden">(Pflichtfeld)</span>
        </label>
        <slot :described-by="describedBy" :error="hasError" :error-id="errorId" :error-message="errorMessage"
              :field-id="fieldId" :help-id="helpId" />
        <small v-if="help" :id="helpId" class="message form-text text-muted">{{ help }}</small>
        <div v-if="hasError" :id="errorId" :key="errorId + String(errorMessage)" class="message invalid-feedback d-block">
            {{ errorMessage }}
        </div>
    </div>
</template>

<script>
import ValueCheck from "../elements/ValueCheck";

export default {
    name: "FormGroup",
    components: {ValueCheck},
    props: {
        id: String,
        name: String,
        label: String,
        help: String,
        preLabel: String,
        required: {
            type: Boolean,
            default: false,
        },
        value: { type: null },
        isCheckedItem: {
            type: Boolean,
            default: false,
        },
        inputId: String,
        error: {
            type: [String, Array],
            default: null,
        },
    },
    computed: {
        fieldId() {
            if (this.inputId) return this.inputId;
            if (this.id) return `${this.id}Input`;
            return '';
        },
        helpId() {
            return this.id ? `${this.id}Help` : '';
        },
        errorId() {
            return this.id ? `${this.id}Error` : '';
        },
        pageError() {
            if (!this.name) return null;
            return this.$page.props.errors?.[this.name] || null;
        },
        errorMessage() {
            const message = this.error ?? this.pageError;
            if (!message) return '';
            return Array.isArray(message) ? message.join(' ') : message;
        },
        hasError() {
            return this.errorMessage !== '';
        },
        describedBy() {
            return [this.help ? this.helpId : null, this.hasError ? this.errorId : null].filter(Boolean).join(' ');
        },
        groupClasses() {
            return {
                'form-group-required': this.required,
                'form-field-invalid': this.hasError,
            };
        },
    },
}
</script>

<style scoped>
.message {
    font-size: .8em;
}

.form-label {
    font-weight: 500;
}

</style>

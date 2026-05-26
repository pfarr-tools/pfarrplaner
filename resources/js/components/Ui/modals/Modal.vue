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
    <form @submit.prevent="closeModal">
        <!-- Modal -->
        <div class="modal fade show d-block" :data-dusk="$attrs['data-dusk']" :data-bs-backdrop="allowCancel ? '' : 'static'" tabindex="-1" aria-hidden="true"
             @keyup.esc="handleEsc" @keyup.enter="closeModal" @click="handleEsc">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document" :style="{maxWidth: maxWidth, minHeight: minHeight || 'auto', maxHeight: maxHeight || 'auto'}"
                 @click.stop="nop">
                <div class="modal-content" :style="{maxWidth: maxWidth, minHeight: minHeight || 'auto', maxHeight: maxHeight || 'auto'}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ title }}</h5>
                        <button v-if="allowCancel" type="button" class="btn-close" data-dismiss="modal"
                                aria-label="Fenster schließen" @click.prevent="cancelModal('window')">
                            <span aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="modal-body" ref="body">
                        <slot />
                    </div>
                    <div class="modal-footer">
                        <slot name="additional-buttons" />
                        <button  v-if="allowClose" type="button" :class="'btn btn-'+submitButtonType"
                                 @click.prevent="closeModal">{{ closeButtonLabel }}
                        </button>
                        <button v-if="allowCancel" type="button" :class="'btn btn-'+cancelButtonType" @click.prevent="cancelModal('button')">
                            {{ cancelButtonLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
export default {
    name: "Modal",
    props: {
        title: String,
        allowCancel: {
            type: Boolean,
            default: true,
        },
        allowClose: {
            type: Boolean,
            default: true,
        },
        show: {
            type: Boolean,
            default: true,
        },
        closeButtonLabel: {
            type: String,
            default: 'Schließen',
        },
        cancelButtonLabel: {
            type: String,
            default: 'Abbrechen',
        },
        submitButtonType: {
            type: String,
            default: 'primary',
        },
        cancelButtonType: {
            type: String,
            default: 'secondary',
        },
        maxWidth: {
            type: String,
            default: '90vw',
        },
        minHeight: String,
        maxHeight: String,
    },
    data() {
        return {
            showModal: this.show,
        }
    },
    mounted() {
        document.body.classList.add("modal-open");
        this.$emit('shown', this.$refs.body);
    },
    methods: {
        closeModal() {
            document.body.classList.remove("modal-open");
            this.$emit('close');
        },
        cancelModal(source) {
            document.body.classList.remove("modal-open");
            this.$emit('cancel', source);
        },
        nop() {},
        handleEsc() {
            if (!this.allowCancel) return;
            this.cancelModal('esc');
        },
    }
}
</script>

<style scoped>

.modal {
    background-color: #000A;
}

.modal-dialog {
    max-height: 90vh;
    max-width: 90vw;
    overflow-y: auto;
}

</style>

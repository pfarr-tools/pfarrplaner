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
    <div>
        <div class="mb-3 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" @click.prevent="copyToClipboard" :disabled="loading || !prompt">
                <span class="mdi mdi-content-copy me-1"></span>Prompt kopieren
            </button>
        </div>

        <div v-if="loading" class="alert alert-info mb-0">
            Prompt wird geladen...
        </div>
        <div v-else-if="error" class="alert alert-danger mb-0">
            {{ error }}
        </div>
        <form-textarea v-else label="Vorgeschlagener Prompt" v-model="prompt" rows="20"/>
    </div>
</template>

<script>
import FormTextarea from "../../Ui/forms/FormTextarea.vue";

export default {
    name: "AIPromptLiturgySheetDialog",
    components: {FormTextarea},
    props: ['service', 'sheet'],
    data() {
        return {
            loading: true,
            error: null,
            prompt: '',
        };
    },
    mounted() {
        this.loadData();
    },
    methods: {
        loadData() {
            this.loading = true;
            this.error = null;
            axios.get(route('liturgy.dialog', {service: this.service.slug, key: this.sheet.key}))
                .then(response => {
                    this.prompt = response.data.prompt || '';
                })
                .catch(() => {
                    this.error = 'Der Prompt konnte nicht geladen werden.';
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        copyToClipboard() {
            navigator.clipboard.writeText(this.prompt);
        },
    },
}
</script>

<style scoped>

</style>

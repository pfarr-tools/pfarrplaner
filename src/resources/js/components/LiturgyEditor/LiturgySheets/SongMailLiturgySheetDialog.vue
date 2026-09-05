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
        <div class="alert alert-warning">
            Der Text wird in die Zwischenablage kopiert. Anschließend wird eine neue E-Mail mit Betreff und Empfängern geöffnet.
        </div>

        <div class="mb-3 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" @click.prevent="sendMail" :disabled="loading || !body">
                <span class="mdi mdi-email-arrow-right-outline me-1"></span>Nachricht erstellen
            </button>
            <button class="btn btn-outline-secondary" @click.prevent="copyToClipboard" :disabled="loading || !body">
                <span class="mdi mdi-content-copy me-1"></span>Text kopieren
            </button>
        </div>

        <div v-if="loading" class="alert alert-info mb-0">
            E-Mailtext wird geladen...
        </div>
        <div v-else-if="error" class="alert alert-danger mb-0">
            {{ error }}
        </div>
        <form-textarea v-else label="E-Mailtext" v-model="body" rows="18"/>
    </div>
</template>

<script>
import FormTextarea from "../../Ui/forms/FormTextarea.vue";

export default {
    name: "SongMailLiturgySheetDialog",
    components: {FormTextarea},
    props: ['service', 'sheet'],
    data() {
        return {
            loading: true,
            error: null,
            body: '',
            subject: '',
            recipients: [],
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
                    this.body = response.data.body || '';
                    this.subject = response.data.subject || '';
                    this.recipients = response.data.recipients || [];
                })
                .catch(() => {
                    this.error = 'Der E-Mailtext konnte nicht geladen werden.';
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        copyToClipboard() {
            navigator.clipboard.writeText(this.body);
        },
        sendMail() {
            this.copyToClipboard();
            window.location.href = 'mailto:' + this.recipients.join(',') + '?subject=' + encodeURIComponent(this.subject);
        },
    },
}
</script>

<style scoped>

</style>

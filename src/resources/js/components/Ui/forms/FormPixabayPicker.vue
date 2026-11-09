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
    <div class="pixabay-picker">
        <div class="input-group">
            <input
                v-model.trim="search"
                class="form-control"
                type="search"
                placeholder="Auf Pixabay suchen..."
                @keyup.enter.prevent="searchImages(1)"
            />
            <button
                type="button"
                class="btn btn-primary"
                :disabled="loading || !search"
                @click="searchImages(1)"
            >
                <span
                    class="mdi"
                    :class="loading ? 'mdi-loading mdi-spin' : 'mdi-magnify'"
                    aria-hidden="true"
                ></span>
                Suchen
            </button>
        </div>
        <small class="form-text text-muted"
            >Die Suchergebnisse werden in einem Auswahlfenster angezeigt.</small
        >
        <modal
            v-if="dialogOpen"
            title="Bild von Pixabay auswählen"
            max-width="1100px"
            :allow-close="false"
            @cancel="dialogOpen = false"
        >
            <div class="input-group mb-3">
                <input
                    v-model.trim="search"
                    class="form-control"
                    type="search"
                    aria-label="Pixabay-Suche"
                    @keyup.enter.prevent="searchImages(1)"
                />
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="loading || !search"
                    @click="searchImages(1)"
                >
                    Suchen
                </button>
            </div>
            <div
                v-if="loading"
                class="pixabay-loading"
            >
                <span
                    class="mdi mdi-loading mdi-spin"
                    aria-hidden="true"
                ></span>
                Ergebnisse werden geladen …
            </div>
            <div
                v-else-if="error"
                class="alert alert-danger"
            >
                {{ error }}
            </div>
            <template v-else>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small"
                        >Seite {{ page }} / {{ pages }} · {{ totalHits }} Ergebnisse</span
                    >
                    <div
                        class="btn-group btn-group-sm"
                        role="group"
                        aria-label="Pixabay-Seiten"
                    >
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            :disabled="page <= 1"
                            @click="searchImages(page - 1)"
                        >
                            <span
                                class="mdi mdi-chevron-left"
                                aria-hidden="true"
                            ></span>
                            Zurück
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            :disabled="page >= pages"
                            @click="searchImages(page + 1)"
                        >
                            Weiter
                            <span
                                class="mdi mdi-chevron-right"
                                aria-hidden="true"
                            ></span>
                        </button>
                    </div>
                </div>
                <div class="pixabay-results">
                    <button
                        v-for="result in results"
                        :key="result.id"
                        type="button"
                        class="pixabay-result"
                        :title="`Bild von ${result.user || 'Pixabay'} auswählen`"
                        @click="selectResult(result)"
                    >
                        <img
                            :src="result.webformatURL || result.previewURL"
                            :alt="result.tags || 'Pixabay-Bild'"
                        />
                        <span>{{ result.user || 'Pixabay' }}</span>
                    </button>
                </div>
                <div
                    v-if="!results.length"
                    class="alert alert-info mb-0"
                >
                    Keine Bilder gefunden.
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import FormGroup from './FormGroup'
import Modal from '../modals/Modal'
export default {
    name: 'FormPixabayPicker',
    emits: ['input'],
    components: { Modal, FormGroup },
    data() {
        return {
            page: 1,
            pages: 0,
            token: this.$page.props.currentUser.data.api_token,
            totalHits: 0,
            results: [],
            search: '',
            loading: false,
            error: '',
            dialogOpen: false,
        }
    },
    methods: {
        /** Search Pixabay and open or refresh the paginated result dialog. */
        searchImages(page = 1) {
            if (!this.search) return
            if (typeof page !== 'number' || !Number.isFinite(page)) page = 1
            page = Math.max(1, Math.floor(page))
            this.loading = true
            this.error = ''
            this.page = page
            this.dialogOpen = true
            const searchUrl = route('api.pixabay.query', { query: this.search })
            this.$api()
                .get(`${searchUrl}/${page}`)
                .then((response) => {
                    this.totalHits = response.data.totalHits || 0
                    this.results = response.data.hits || []
                    this.pages = Math.max(1, Math.ceil(this.totalHits / 20))
                })
                .catch(() => {
                    this.results = []
                    this.totalHits = 0
                    this.pages = 0
                    this.error = 'Die Pixabay-Suche konnte nicht geladen werden.'
                })
                .finally(() => {
                    this.loading = false
                })
        },
        /** Emit the selected Pixabay hit to the shared local upload flow. */
        selectResult(result) {
            this.$emit('input', result)
            this.dialogOpen = false
        },
    },
}
</script>

<style scoped>
.pixabay-loading {
    padding: 3rem;
    text-align: center;
    color: var(--bs-secondary-color);
}
.pixabay-results {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: 0.75rem;
    max-height: 58vh;
    overflow-y: auto;
}
.pixabay-result {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.35rem;
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background: var(--bs-body-bg);
    text-align: left;
}
.pixabay-result:hover,
.pixabay-result:focus-visible {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.15);
}
.pixabay-result img {
    display: block;
    width: 100%;
    height: 8rem;
    object-fit: cover;
}
.pixabay-result span {
    overflow: hidden;
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>

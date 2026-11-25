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
    <admin-layout title="Tokens verwalten">
        <card>
            <card-header>API-Tokens verwalten</card-header>
            <card-body>
                <table
                    v-if="tokens && tokens.length"
                    class="table table-striped"
                >
                    <thead>
                        <tr>
                            <th>Bezeichnung</th>
                            <th>Geltungsbereich</th>
                            <th>Angelegt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in tokens"
                            :key="item.id"
                        >
                            <td>{{ item.name }}</td>
                            <td>
                                <span
                                    class="badge"
                                    :class="
                                        item.scope_type === 'church_extranet'
                                            ? 'bg-primary'
                                            : 'bg-secondary'
                                    "
                                >
                                    {{ item.scope_label || 'Persönlich' }}
                                </span>
                            </td>
                            <td>{{ formatCreatedAt(item.created_at) }}</td>
                            <td class="text-end">
                                <button
                                    class="btn btn-sm btn-danger"
                                    title="Token löschen"
                                    @click.prevent="deleteToken(item)"
                                >
                                    <span class="mdi mdi-delete"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p
                    v-else
                    class="text-muted mb-0"
                >
                    Es sind derzeit keine aktiven API-Tokens vorhanden.
                </p>
                <div v-if="token">
                    <hr />
                    <h3>Neues Token "{{ token.accessToken.name }}" hinzugefügt.</h3>
                    <p>
                        Das folgende Token wird einmalig hier angezeigt. Bitte kopiere den Inhalt an
                        einen sicheren Ort:
                    </p>
                    <pre>{{ token.plainTextToken }}</pre>
                </div>
                <hr />
                <h3>Neues Token hinzufügen</h3>
                <form-input
                    v-model="newTokenTitle"
                    label="Bezeichnung"
                    name="newTokenTitle"
                ></form-input>
                <div
                    v-if="canCreateChurchTokens && churchTokenCities.length > 0"
                    class="mb-3"
                >
                    <label
                        for="newTokenCitySelect"
                        class="form-label"
                    >
                        Geltungsbereich / Kirchengemeinde
                    </label>
                    <select
                        id="newTokenCitySelect"
                        v-model="selectedCityId"
                        name="city_id"
                        class="form-select"
                    >
                        <option value="">Persönliches API-Token (alle eigenen Bereiche)</option>
                        <option
                            v-for="city in churchTokenCities"
                            :key="city.id"
                            :value="String(city.id)"
                        >
                            Extranet-Service-API: {{ city.name }}
                        </option>
                    </select>
                    <small class="form-text text-muted">
                        Ein gemeindebezogener Schlüssel berechtigt ausschließlich zur Verwaltung von
                        Gottesdiensten und Veranstaltungen der gewählten Kirchengemeinde über die
                        Extranet-API.
                    </small>
                </div>
                <button
                    class="btn btn-success"
                    title="Token hinzufügen"
                    @click.prevent="addToken"
                >
                    Neues Token anlegen
                </button>
            </card-body>
        </card>
    </admin-layout>
</template>

<script>
import Card from '../components/Ui/cards/card'
import CardBody from '../components/Ui/cards/cardBody'
import CardHeader from '../components/Ui/cards/cardHeader'
import FormInput from '../components/Ui/forms/FormInput'

/**
 * Displays and manages personal and church-scoped Sanctum API tokens.
 *
 * Props:
 * - tokens: Persisted Sanctum tokens with scope metadata.
 * - token: Newly generated plain-text token payload shown once after creation.
 * - canCreateChurchTokens: Whether the current user may issue church-scoped Extranet keys.
 * - churchTokenCities: Writable churches selectable when issuing church-scoped Extranet keys.
 */
export default {
    name: 'TokensPage',
    components: { FormInput, CardHeader, CardBody, Card },
    props: {
        tokens: {
            type: Array,
            default: () => [],
        },
        token: {
            type: Object,
            default: null,
        },
        canCreateChurchTokens: {
            type: Boolean,
            default: false,
        },
        churchTokenCities: {
            type: Array,
            default: () => [],
        },
    },
    /**
     * Initialize form state for creating a new API token.
     *
     * @returns {{newTokenTitle: string, selectedCityId: string}} Reactive form fields.
     */
    data() {
        return {
            newTokenTitle: '',
            selectedCityId: '',
        }
    },
    methods: {
        /**
         * Format a token creation timestamp in the German locale when moment/dayjs is available.
         *
         * @param {string|null} value - ISO timestamp of token creation.
         * @returns {string} Human-readable creation timestamp.
         */
        formatCreatedAt(value) {
            if (!value) {
                return ''
            }
            if (typeof window !== 'undefined' && typeof window.moment === 'function') {
                return window.moment(value).locale('de').format('LLLL')
            }

            return value
        },
        /**
         * Submit the token creation form with optional church scoping.
         *
         * @returns {void}
         */
        addToken() {
            this.$inertia.post(route('tokens.create'), {
                title: this.newTokenTitle,
                city_id: this.selectedCityId ? Number(this.selectedCityId) : null,
            })
        },
        /**
         * Prompt for confirmation and revoke the selected Sanctum token.
         *
         * @param {{id: number}} item - Token metadata row to delete.
         * @returns {void}
         */
        deleteToken(item) {
            if (!confirm('Willst du diesen Zugangsschlüssel wirklich löschen?')) return
            this.$inertia.delete(route('tokens.destroy', { token: item.id }))
        },
    },
}
</script>

<style scoped></style>

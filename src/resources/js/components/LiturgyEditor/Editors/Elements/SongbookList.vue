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
    <div class="songbook-list">
        <label>Steht in folgenden Liederbüchern:</label>
        <table class="table table-striped table-hover" :key="mySongbooks.length">
            <tbody>
            <tr v-for="(songbook, songbookIndex) in mySongbooks" :key="`${songbook.id || songbook.pivot?.id || `songbook-${songbookIndex}`}-${editing === songbookIndex ? 'editing' : 'view'}`">
                <td colspan="2" v-if="editing == songbookIndex">
                    <songbook-select
                        :songbooks="allSongbooks"
                        :model-value="mySongbooks[songbookIndex]"
                        :pivot="songbook.pivot"
                        @update:modelValue="updateSongbook(songbookIndex, $event)"
                    />
                </td>
                <td v-if="editing != songbookIndex">{{ songbook.code }}</td>
                <td v-if="editing != songbookIndex">{{ songbook.name }}</td>
                <td v-if="editing != songbookIndex"><div v-if="songbook.pivot.color" class="color" :style="songbook.pivot.color ? 'background-color: '+songbook.pivot.color : ''" :title="songbook.color || ''"></div></td>
                <td>
                    <div v-if="editing == songbookIndex">
                        <form-input v-model="songbook.pivot.reference" />
                    </div>
                    <div v-else>{{ songbook.pivot.reference || ''}}</div>
                </td>
                <td v-if="editing == songbookIndex">
                    <form-selectize :options="colors" v-model="songbook.pivot.color" />
                </td>
                <td class="text-end">
                    <nav-button icon="mdi mdi-pencil" title="Eintrag bearbeiten" v-if="editing != songbookIndex"
                                class="btn-sm"
                                force-no-text force-icon @click="editEntry(songbookIndex)" />
                    <nav-button icon="mdi mdi-delete" title="Eintrag löschen" v-if="editing != songbookIndex"
                                class="btn-sm" type="danger"
                                force-no-text force-icon @click="deleteEntry(songbookIndex)" />
                    <nav-button icon="mdi mdi-call-split" title="Eintrag zu neuem Lied umwandeln"
                                v-if="allowSplit && (editing != songbookIndex) && (mySongbooks.length > 1)"
                                class="btn-sm"
                                force-no-text force-icon @click="splitOffEntry(songbook)" />
                    <nav-button icon="mdi mdi-check" v-if="editing == songbookIndex"
                                class="btn-sm"
                                type="success" title="Bestätigen"
                                force-no-text force-icon @click="confirmEdit" />
                </td>
            </tr>
            </tbody>
        </table>
        <hr />
        <nav-button icon="mdi mdi-plus" title="Eintrag hinzufügen" class="btn-sm"
                    @click="addEntry">Eintrag hinzufügen</nav-button>
    </div>
</template>

<script>
import NavButton from "../../../Ui/buttons/NavButton";
import FormInput from "../../../Ui/forms/FormInput";
import SongbookSelect from "./SongbookSelect";
import FormSelectize from "../../../Ui/forms/FormSelectize.vue";
export default {
    name: "SongbookList",
    emits: ['update:modelValue'],
    components: {FormSelectize, SongbookSelect, FormInput, NavButton},
    props: ['modelValue', 'allowSplit'],
    created() {
        this.$api().get(route('api.songbooks.index'))
        .then(result => {
            this.allSongbooks = result.data;
        });
        this.$api().get(route('api.songbooks.colors'))
        .then(result => {
            this.colors = result.data;
        });
    },
    data() {
        return {
            editing: -1,
            mySongbooks: [...(this.modelValue || [])],
            allSongbooks: [],
            colors: [],
        };
    },
    watch: {
        modelValue: {
            deep: true,
            handler(val) {
                if (this.editing === -1) {
                    this.mySongbooks = [...val];
                }
            },
        },
    },
    methods: {
        editEntry(songbookIndex) {
            this.editing = songbookIndex;
        },
        updateSongbook(songbookIndex, songbook) {
            this.mySongbooks.splice(songbookIndex, 1, songbook);
            this.$emit('update:modelValue', [...this.mySongbooks]);
        },
        confirmEdit() {
            this.editing = -1;
            this.$emit('update:modelValue', [...this.mySongbooks]);
        },
        deleteEntry(songbookIndex) {
            this.mySongbooks.splice(songbookIndex, 1);
            this.$emit('update:modelValue', [...this.mySongbooks]);
        },
        addEntry() {
            this.mySongbooks.push({
                code: '',
                description: '',
                id: null,
                image: null,
                isbn: null,
                name: '',
                pivot: {
                    reference: '',
                    song_id: null,
                    songbook_id: null,
                    color: '',
                }
            });
            this.editing = this.mySongbooks.length - 1;
            this.$emit('update:modelValue', [...this.mySongbooks]);
        },
        splitOffEntry(songbook) {
            if (!confirm('Willst du wirklich ein separates Lied aus diesem Eintrag erstellen?')) return;
            this.$inertia.post(route('song.songbook.split', {song: songbook.pivot.song_id, reference: songbook.pivot.songbook_id }), {}, {preserveState: false});
        }
    }
}
</script>

<style scoped>
    div.color {
        margin-top: .5em;
        height: 10px;
        width: 10px;
        border: solid 1px lightgray;
        background-color: transparent;
         border-radius: 0;
    }
</style>

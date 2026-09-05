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
    <form submit.prevent="save">
        <div class="row">
            <div class="col-sm-9">
                <div class="liturgy-editor-details-pane p-1" v-scroll-to :key="state">
                    <component :is="editorComponent" :element="editedElement" v-model="editedElement" :service="service"
                               :agenda-mode="agendaMode" :markers="markers" @unfocus="$emit('unfocus')"/>
                </div>
            </div>
            <div class="col-sm-3">
                <people-pane :service="service" :element="editedElement"
                             :agenda-mode="agendaMode"
                             @close="doneEditingResponsibles(item)"
                             :ministries="lists.ministries"/>
            </div>
        </div>
        <div class="p-1">
            <time-fields v-if="element.data_type != 'block'"
                         :service="service" :element="editedElement" :agenda-mode="agendaMode"/>
            <div class="form-group">
                <button class="btn btn-primary me-1" @click.prevent.stop="save">Speichern</button>
                <button class="btn btn-secondary" @click.prevent.stop="cancel">Abbrechen</button>
            </div>
        </div>
    </form>
</template>

<script>

import BlockEditor from "../Editors/BlockEditor";
import FreetextEditor from "../Editors/FreetextEditor";
import PsalmEditor from "../Editors/PsalmEditor";
import ReadingEditor from "../Editors/ReadingEditor";
import SermonEditor from "../Editors/SermonEditor";
import SongEditor from "../Editors/SongEditor";
import __ from "lodash";
import TimeFields from "../Editors/Elements/TimeFields";
import PeoplePane from "./PeoplePane.vue";

export default {
    name: "DetailsPane",
    components: {
        PeoplePane,
        TimeFields,
        BlockEditor,
        FreetextEditor,
        PsalmEditor,
        ReadingEditor,
        SermonEditor,
        SongEditor
    },
    props: {
        element: Object,
        service: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        },
        markers: {
            type: Object,
            default: false,
        }
    },
    inject: ['lists'],
    data() {
        return {
            savedState: Object.assign({}, this.element),
            editorComponent: this.element.data_type.charAt(0).toUpperCase() + this.element.data_type.slice(1) + 'Editor',
            editedElement: this.element,
            state: 1,
        }
    },
    methods: {
        save: function () {
            if (this.editedElement.data_type == 'block') {
                this.$api().patch(route('api.liturgy.block.update', {
                    block: this.editedElement.id,
                }), this.editedElement).then(response => {
                    this.editedElement.editing = false;
                    this.$emit('unfocus', this.editedElement);
                })
            } else {
                this.$api().patch(route('api.liturgy.item.update', {
                    item: this.editedElement.id,
                }), this.editedElement).then(response => {
                    this.editedElement.editing = false;
                    this.$emit('unfocus', this.editedElement);
                });
            }
        },
        cancel: function () {
            this.editedElement = Object.assign({}, this.savedState);
            this.state++;
            this.editedElement.editing = false;
            this.$emit('unfocus', this.savedState);
        }
    }
}
</script>

<style scoped>

</style>

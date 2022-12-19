<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.de
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarrplaner/pfarrplaner
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
    <div class="liturgy-item-sermon-editor">
        <div class="form-group">
            <label for="title">Titel im Ablaufplan</label>
            <input class="form-control" v-model="editedElement.title" v-focus/>
        </div>
        <div v-if="myService.sermon === null">
            <p>Für diesen Gottesdienst ist noch keine Predigt angelegt. Hier kannst du eine bestehende Predigt auswählen oder eine neue anlegen.</p>
            <form-selectize v-if="lists.sermons.length > 0" :options="lists.sermons" id-key="id"
                            title-key="title"
                            label="Bestehende Predigt auswählen"
                            :settings="sermonSelectizeSettings"
                            @input="setSermon($event, item)"/>
            <inertia-link :href="route('service.sermon.editor', {service: myService.slug})"
                          @click.stop=""
                          class="btn btn-success"
                          title="Hier klicken, um die Predigt jetzt anzulegen">
                Neue Predigt anlegen
            </inertia-link>
        </div>
        <div v-else>
            <label>Predigt</label>
            <div class="p-2">
                <inertia-link :href="route('sermon.editor', {sermon: myService.sermon.id})"
                              @click.stop="" title="Hier klicken, um die Predigt zu bearbeiten">
                    {{ myService.sermon.title }}<span
                    v-if="myService.sermon.subtitle">: {{ myService.sermon.subtitle }}</span>
                </inertia-link>
                <button class="btn btn-sm btn-light ml-1" @click="setSermon(null, item)"
                        title="Verknüpfung mit dieser Predigt aufheben">
                    <span class="mdi mdi-link-off"></span>
                </button>
                <div v-if="myService.sermon.reference" class="text-sm text-muted">
                    {{ myService.sermon.reference }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import TimeFields from "./Elements/TimeFields";
import FormSelectize from "../../Ui/forms/FormSelectize.vue";

export default {
    name: "SermonEditor",
    components: {FormSelectize, TimeFields},
    props: {
        element: Object,
        service: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        }
    },
    inject: ['lists'],
    data() {
        var e = this.element;
        return {
            editedElement: e,
            myService: this.service,
            sermonSelectizeSettings: {
                searchField: ['title'],
                render: {
                    option: function (item, escape) {
                        var t= '<div>'+escape(item.title)+(item.subtitle ? ': '+escape(item.subtitle) : '');
                        if (item.reference) t+= '<div class="text-sm text-muted">'+escape(item.reference)+'</div>';
                        t += '</div>';
                        return t;
                    }
                }
            },
        };
    },
    methods: {
        save: function () {
            this.$inertia.patch(route('liturgy.item.update', {
                service: this.service.id,
                block: this.element.liturgy_block_id,
                item: this.element.id,
            }), this.element, {preserveState: false});
        },
        setSermon(e) {
            this.myService.sermon_id = e;
            axios.patch(route('service.setsermon', this.myService.slug), {sermon_id: e ?? null});
            if (e) {
                this.lists.sermons.forEach(sermon => {
                    if (sermon.id == e) this.myService.sermon = sermon;
                });
            } else {
                this.myService.sermon = null;
            }
        },
    }
}
</script>

<style scoped>
.liturgy-item-freetext-editor {
    padding: 5px;
}
</style>

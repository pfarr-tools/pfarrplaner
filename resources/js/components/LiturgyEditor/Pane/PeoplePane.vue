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
    <div class="liturgy-editor-people-pane pe-2">
        <form @submit.prevent="save">
            <label>Verantwortlich</label>
            <Multiselect
                name="data[responsible][]"
                v-model="editedElement.data.responsible"
                mode="tags"
                :groups="true"
                :options="groupedOptions"
                value-prop="id"
                label="name"
                :searchable="true"
                :create-option="createFreeOption"
                locale="de"
                :no-results-text="{ de: 'Keine Ergebnisse gefunden', en: 'No results found' }"
                :no-options-text="{ de: 'Die Liste ist leer', en: 'The list is empty' }"
            />
            <div class="mt-2" v-if="agendaMode">
                <label>Beschreibung (Vorlage)</label>
                <div class="tiptap-toolbar btn-group btn-group-sm mb-1">
                    <button type="button" class="btn btn-outline-secondary" :class="{active: editor.isActive('bold')}"
                            @click.prevent="editor.chain().focus().toggleBold().run()" title="Fett">
                        <span class="mdi mdi-format-bold"/>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" :class="{active: editor.isActive('italic')}"
                            @click.prevent="editor.chain().focus().toggleItalic().run()" title="Kursiv">
                        <span class="mdi mdi-format-italic"/>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" :class="{active: editor.isActive('underline')}"
                            @click.prevent="editor.chain().focus().toggleUnderline().run()" title="Unterstrichen">
                        <span class="mdi mdi-format-underline"/>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" :class="{active: editor.isActive('heading', {level: 1})}"
                            @click.prevent="editor.chain().focus().toggleHeading({level: 1}).run()" title="Überschrift">
                        <span class="mdi mdi-format-header-1"/>
                    </button>
                </div>
                <editor-content :editor="editor" class="form-control tiptap-editor" />
            </div>
            <div class="mt-1" v-else-if="editedElement.data.agenda_description">
                <small class="text-muted" v-html="editedElement.data.agenda_description"/>
            </div>
        </form>
    </div>
</template>

<script>
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';

export default {
    name: "PeoplePane",
    props: {
        element: Object,
        service: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        },
        ministries: {
            type: Object,
            default() { return {}; },
        },
    },
    components: { Multiselect, EditorContent },
    data() {
        var e = this.element;
        var emptyOption = { name: '', type: '' };
        if (undefined == e.data.responsible) e.data.responsible = [emptyOption];
        if (e.data.responsible.length == 0) e.data.responsible = [emptyOption];
        if (undefined == e.data.agenda_description) e.data.agenda_description = '';

        var options = [];
        const basicMinistries = {
            pastors: this.$page.props.labels.pastor,
            organists: this.$page.props.labels.organist,
            sacristans: this.$page.props.labels.sacristan,
        };
        for (var ministryIndex in basicMinistries) {
            options.push({ id: 'ministry:' + ministryIndex, name: basicMinistries[ministryIndex], category: basicMinistries[ministryIndex], type: 'users' });
            this.service[ministryIndex].forEach(person => {
                options.push({ id: 'user:' + person.id, name: person.name, category: basicMinistries[ministryIndex], type: 'user-check' });
            });
        }
        var knownMinistries = this.service.ministriesByCategory;
        Object.keys(this.ministries).forEach(ministry => {
            if (knownMinistries[ministry]) {
                options.push({ id: 'ministry:' + ministry, name: ministry, category: ministry, type: 'users' });
                knownMinistries[ministry].forEach(person => {
                    options.push({ id: 'user:' + person.id, name: person.name, category: ministry, type: 'user-check' });
                });
            }
        }, this);
        e.data.responsible.forEach(item => {
            if (item && typeof item == 'string' && item.substr(0, 5) == 'free:') {
                options.push({ id: item, name: item.substr(5), category: 'Eigene Eingaben', type: 'user-times' });
            }
        });

        return {
            editedElement: e,
            options: options,
            editor: new Editor({
                content: e.data.agenda_description || '',
                extensions: [StarterKit, Underline],
                onUpdate: ({ editor }) => {
                    this.editedElement.data.agenda_description = editor.getHTML();
                },
            }),
        };
    },
    computed: {
        groupedOptions() {
            const groups = {};
            this.options.forEach(opt => {
                const cat = opt.category || 'Sonstige';
                if (!groups[cat]) groups[cat] = [];
                groups[cat].push(opt);
            });
            return Object.entries(groups).map(([label, opts]) => ({ label, options: opts }));
        },
    },
    beforeUnmount() {
        this.editor.destroy();
    },
    methods: {
        createFreeOption(query) {
            const newOpt = { id: 'free:' + query, name: query, category: 'Eigene Eingaben', type: 'user-times' };
            this.options.push(newOpt);
            return newOpt;
        },
    },
};
</script>

<style scoped>
.tiptap-editor :deep(.ProseMirror) {
    min-height: 80px;
    outline: none;
}
</style>

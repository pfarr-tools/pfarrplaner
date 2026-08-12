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
    <div class="rich-text-editor form-group">
        <label>{{ myLabel }}</label>
        <div class="tiptap-toolbar btn-toolbar mb-1" v-if="editor">
            <button v-if="mySettings.toolbar.bold" class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="editor.chain().focus().toggleBold().run()"
                    :class="{active: editor.isActive('bold')}" title="Fett"><b>B</b></button>
            <button v-if="mySettings.toolbar.italic" class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="editor.chain().focus().toggleItalic().run()"
                    :class="{active: editor.isActive('italic')}" title="Kursiv"><i>I</i></button>
            <button v-if="mySettings.toolbar.underline" class="btn btn-sm btn-outline-secondary me-2"
                    @click.prevent="editor.chain().focus().toggleUnderline().run()"
                    :class="{active: editor.isActive('underline')}" title="Unterstrichen"><u>U</u></button>
            <button v-if="mySettings.toolbar.header" class="btn btn-sm btn-outline-secondary me-1"
                    @click.prevent="editor.chain().focus().toggleHeading({level:1}).run()"
                    :class="{active: editor.isActive('heading',{level:1})}" title="Überschrift">H1</button>
            <button v-if="mySettings.toolbar.blockquote" class="btn btn-sm btn-outline-secondary me-2"
                    @click.prevent="editor.chain().focus().toggleBlockquote().run()"
                    :class="{active: editor.isActive('blockquote')}" title="Zitat">&ldquo;</button>
            <template v-if="mySettings.toolbar.formats && mySettings.toolbar.formats.length > 0">
                <button class="btn btn-sm btn-outline-secondary me-1"
                        @click.prevent="editor.chain().focus().toggleOrderedList().run()"
                        :class="{active: editor.isActive('orderedList')}" title="Nummerierte Liste">1.</button>
                <button class="btn btn-sm btn-outline-secondary me-2"
                        @click.prevent="editor.chain().focus().toggleBulletList().run()"
                        :class="{active: editor.isActive('bulletList')}" title="Aufzählung">&bull;</button>
            </template>
            <button v-if="mySettings.toolbar.clean" class="btn btn-sm btn-outline-secondary"
                    @click.prevent="editor.chain().focus().unsetAllMarks().clearNodes().run()"
                    title="Formatierung entfernen">&#10005;</button>
        </div>
        <editor-content :editor="editor" class="form-control tiptap-editor" />
    </div>
</template>


<script>
import { markRaw } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';

export default {
    name: "RichTextEditor",
    emits: ['update:modelValue'],
    components: { EditorContent },
    props: ['modelValue', 'settings', 'label'],
    inject: ['lists'],
    data() {
        let defaultSettings = {
            toolbar: {
                bold: false,
                italic: false,
                underline: false,
                header: false,
                blockquote: false,
                formats: {},
            },
        };
        let mySettings = { ...defaultSettings, ...this.settings };
        const initContent = this.modelValue || '';

        return {
            myLabel: this.label || 'Inhalt',
            mySettings,
            editor: markRaw(new Editor({
                content: initContent,
                extensions: [
                    StarterKit,
                    Underline,
                    Placeholder.configure({ placeholder: 'Hier Text eingeben...' }),
                ],
                onUpdate: ({ editor }) => {
                    this.$emit('update:modelValue', editor.getHTML());
                },
            })),
        }
    },
    watch: {
        modelValue(v) {
            if (v !== this.editor.getHTML()) this.editor.commands.setContent(v || '');
        },
    },
    beforeUnmount() {
        this.editor.destroy();
    },
    methods: {
        insertText(value, withHtml = false) {
            if (!value) return;
            if (withHtml) {
                this.editor.chain().focus().insertContent(String(value)).run();
            } else {
                this.editor.chain().focus().insertContent(String(value)).run();
            }
        },
    }
}
</script>

<style scoped>
.tiptap-editor :deep(.ProseMirror) {
    min-height: 80px;
    outline: none;
    font-family: inherit;
    font-weight: normal;
}
:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}
</style>

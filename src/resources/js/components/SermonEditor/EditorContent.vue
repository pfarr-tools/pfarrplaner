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

</template>

<script>
export default {
    name: "EditorContent",
    emits: ['input'],
    props: {
        editor: {
            default: null,
            type: Object
        },
        value: {
            default: "",
            type: String
        }
    },

    watch: {
        editor: {
            immediate: true,
            handler(editor) {
                if (!editor || !editor.element) return;

                this.editor.setContent(this.value);
                this.editor.on("update", ({ getHTML }) => {
                    this.$emit("input", getHTML());
                });

                this.$nextTick(() => {
                    this.$el.appendChild(editor.element.firstChild);
                    editor.setParentComponent(this);
                });
            }
        },
        value: {
            handler(value) {
                this.editor.setContent(value);
            }
        }
    },

    render(createElement) {
        return createElement("div");
    },

    beforeUnmount() {
        this.editor.element = this.$el;
    }
};
</script>

<style scoped>
.ProseMirror {
    border: 1px solid #ced4da;
     border-radius: 0;
    box-shadow: inset 0 0 0 transparent;
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    padding: .375rem .75rem;
}

.ProseMirror-focused {
    border-color: #80bdff !important;
}


</style>

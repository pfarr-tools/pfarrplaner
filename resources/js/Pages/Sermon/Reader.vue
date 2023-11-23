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

<script>
export default {
    name: "Reader",
    props: ['sermon'],
    methods: {
        highlight(e) {
            var selection = window.getSelection();
            if (!selection || selection.rangeCount < 1) return true;
            var range = selection.getRangeAt(0);
            var node = selection.anchorNode;
            var word_regexp = /^\w*$/;

            // Extend the range backward until it matches word beginning
            while ((range.startOffset > 0) && range.toString().match(word_regexp)) {
                range.setStart(node, (range.startOffset - 1));
            }
            // Restore the valid word match after overshooting
            if (!range.toString().match(word_regexp)) {
                range.setStart(node, range.startOffset + 1);
            }

            // Extend the range forward until it matches word ending
            while ((range.endOffset < node.length) && range.toString().match(word_regexp)) {
                range.setEnd(node, range.endOffset + 1);
            }
            // Restore the valid word match after overshooting
            if (!range.toString().match(word_regexp)) {
                range.setEnd(node, range.endOffset - 1);
            }

            const markElement = document.createElement("mark");
            markElement.className = "highlighted-text";
            range.surroundContents(markElement);

            if (window.getSelection) {
                if (window.getSelection().empty) {  // Chrome
                    window.getSelection().empty();
                } else if (window.getSelection().removeAllRanges) {  // Firefox
                    window.getSelection().removeAllRanges();
                }
            } else if (document.selection) {  // IE?
                document.selection.empty();
            }
        },
    }
}
</script>

<template>
    <admin-layout :title="sermon.title+' :: Leseansicht'">
        <div class="reader" v-html="sermon.text" @click.stop="highlight"/>
    </admin-layout>
</template>

<style scoped>
.reader {
    font-size: 3em;
}

.highlighted-text {
    background-color: green;
    color: white;
}
</style>

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
const WORD_CHARACTER_REGEXP = /[\p{L}\p{M}\p{N}]/u;
const PUNCTUATION_CHARACTER_REGEXP = /[.,;:!?'"“”„‚‘()\-[\]{}…]/u;

export default {
    name: "Reader",
    props: ['sermon'],
    data() {
        return {
            activeHighlightIndex: -1,
            highlightCount: 0,
        };
    },
    computed: {
        activeHighlightNumber() {
            return this.activeHighlightIndex >= 0 ? this.activeHighlightIndex + 1 : 0;
        },
    },
    methods: {
        clearSelection() {
            if (window.getSelection) {
                if (window.getSelection().empty) {
                    window.getSelection().empty();
                } else if (window.getSelection().removeAllRanges) {
                    window.getSelection().removeAllRanges();
                }
            } else if (document.selection) {
                document.selection.empty();
            }
        },
        getHighlights() {
            return this.$refs.reader
                ? Array.from(this.$refs.reader.querySelectorAll('mark.highlighted-text'))
                : [];
        },
        syncHighlights() {
            const highlights = this.getHighlights();
            this.highlightCount = highlights.length;
            if (!highlights.length) {
                this.activeHighlightIndex = -1;
            } else if (this.activeHighlightIndex >= highlights.length) {
                this.activeHighlightIndex = highlights.length - 1;
            }
            return highlights;
        },
        isWordCharacter(character) {
            return WORD_CHARACTER_REGEXP.test(character);
        },
        isPunctuationCharacter(character) {
            return PUNCTUATION_CHARACTER_REGEXP.test(character);
        },
        isHighlightableCharacter(character) {
            return this.isWordCharacter(character) || this.isPunctuationCharacter(character);
        },
        getCaretPosition(event) {
            if (document.caretPositionFromPoint) {
                const position = document.caretPositionFromPoint(event.clientX, event.clientY);
                if (position) {
                    return {
                        node: position.offsetNode,
                        offset: position.offset,
                    };
                }
            }

            if (document.caretRangeFromPoint) {
                const range = document.caretRangeFromPoint(event.clientX, event.clientY);
                if (range) {
                    return {
                        node: range.startContainer,
                        offset: range.startOffset,
                    };
                }
            }

            return null;
        },
        resolveTextNode(position) {
            if (!position || !position.node) return null;

            if (position.node.nodeType === Node.TEXT_NODE) {
                return position;
            }

            if (!position.node.childNodes.length) return null;

            const childIndex = Math.min(position.offset, position.node.childNodes.length - 1);
            const childNode = position.node.childNodes[childIndex];
            if (childNode && childNode.nodeType === Node.TEXT_NODE) {
                return {
                    node: childNode,
                    offset: 0,
                };
            }

            return null;
        },
        getWordRange(position) {
            const textPosition = this.resolveTextNode(position);
            if (!textPosition || !textPosition.node?.textContent) return null;

            const text = textPosition.node.textContent;
            const textLength = text.length;
            if (!textLength) return null;

            let index = Math.min(textPosition.offset, textLength - 1);
            if (!this.isHighlightableCharacter(text.charAt(index)) && index > 0 && this.isHighlightableCharacter(text.charAt(index - 1))) {
                index -= 1;
            }

            if (!this.isHighlightableCharacter(text.charAt(index))) return null;

            if (this.isPunctuationCharacter(text.charAt(index))) {
                const range = document.createRange();
                range.setStart(textPosition.node, index);
                range.setEnd(textPosition.node, index + 1);
                return range;
            }

            let start = index;
            let end = index + 1;

            while (start > 0 && this.isWordCharacter(text.charAt(start - 1))) {
                start -= 1;
            }

            while (end < textLength && this.isWordCharacter(text.charAt(end))) {
                end += 1;
            }

            const range = document.createRange();
            range.setStart(textPosition.node, start);
            range.setEnd(textPosition.node, end);
            return range;
        },
        setActiveHighlight(index, shouldScroll = true) {
            const highlights = this.syncHighlights();

            highlights.forEach((highlight, currentIndex) => {
                highlight.classList.toggle('highlighted-text--active', currentIndex === index);
            });

            if (index < 0 || index >= highlights.length) {
                this.activeHighlightIndex = -1;
                return;
            }

            this.activeHighlightIndex = index;
            if (shouldScroll) {
                highlights[index].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        },
        activateHighlightElement(element, shouldScroll = true) {
            const highlights = this.syncHighlights();
            const highlightIndex = highlights.indexOf(element);
            this.setActiveHighlight(highlightIndex, shouldScroll);
        },
        createHighlight(range) {
            const markElement = document.createElement("mark");
            markElement.className = "highlighted-text";
            range.surroundContents(markElement);
            this.activateHighlightElement(markElement);
        },
        removeHighlight(element) {
            const parent = element.parentNode;
            if (!parent) return;

            while (element.firstChild) {
                parent.insertBefore(element.firstChild, element);
            }

            parent.removeChild(element);
            parent.normalize();
            this.syncHighlights();
            this.setActiveHighlight(-1, false);
        },
        highlight(event) {
            const existingHighlight = event.target.closest('mark.highlighted-text');
            if (existingHighlight) {
                this.removeHighlight(existingHighlight);
                this.clearSelection();
                return true;
            }

            const position = this.getCaretPosition(event);
            const range = this.getWordRange(position);
            if (!range || !range.toString().trim()) return true;

            this.createHighlight(range);
            this.clearSelection();
            return true;
        },
        goToPreviousHighlight() {
            const highlights = this.syncHighlights();
            if (!highlights.length) return;

            const newIndex = this.activeHighlightIndex <= 0
                ? highlights.length - 1
                : this.activeHighlightIndex - 1;
            this.setActiveHighlight(newIndex);
        },
        goToNextHighlight() {
            const highlights = this.syncHighlights();
            if (!highlights.length) return;

            const newIndex = this.activeHighlightIndex >= highlights.length - 1
                ? 0
                : this.activeHighlightIndex + 1;
            this.setActiveHighlight(newIndex);
        },
    }
}
</script>

<template>
    <admin-layout :title="sermon.title+' :: Leseansicht'">
        <template #navbar-right>
            <div class="btn-group" role="group" aria-label="Markierungen">
                <button class="btn btn-light"
                        type="button"
                        title="Zur vorherigen Markierung springen"
                        :disabled="!highlightCount"
                        @click.prevent="goToPreviousHighlight">
                    <span class="mdi mdi-chevron-left me-1"></span>
                    Vorherige
                </button>
                <button class="btn btn-light"
                        type="button"
                        title="Zur nächsten Markierung springen"
                        :disabled="!highlightCount"
                        @click.prevent="goToNextHighlight">
                    Nächste
                    <span class="mdi mdi-chevron-right ms-1"></span>
                </button>
            </div>
            <span class="reader-highlight-counter ms-2">
                {{ activeHighlightNumber }} / {{ highlightCount }}
            </span>
        </template>
        <div ref="reader" class="reader" v-html="sermon.text" @click.stop="highlight"/>
    </admin-layout>
</template>

<style scoped>
.reader {
    font-size: 3em;
    line-height: 1.5;
    padding-bottom: 3rem;
}

.reader :deep(blockquote) {
    margin: 1.25em 0;
    padding: 0.85em 1.2em;
    border-left: 0.2em solid #6c757d;
    background-color: #f8f9fa;
    color: #343a40;
    font-size: 0.92em;
    font-style: italic;
}

.reader :deep(blockquote p:last-child) {
    margin-bottom: 0;
}

.reader-highlight-counter {
    display: inline-flex;
    align-items: center;
    min-width: 4.5rem;
    justify-content: center;
    font-weight: 600;
}

.reader :deep(mark.highlighted-text) {
    background-color: #c0392b;
    color: #fff;
    padding: 0.05em 0.15em;
    border-radius: 0.15em;
}

.reader :deep(mark.highlighted-text.highlighted-text--active) {
    background-color: #8e1b10;
    box-shadow: 0 0 0 0.08em rgba(255, 255, 255, 0.85);
}
</style>

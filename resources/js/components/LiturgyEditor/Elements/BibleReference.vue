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

<template xmlns="http://www.w3.org/1999/html">
    <div class="bible-reference" :class="{'bible-reference-inline' : inline}" :title="text">
        <div v-if="perikope.Bibelstelle" :key="perikope.Bibelstelle.replaceAll(' ', '_')+'Text__'+text">
            <span v-if="title">{{ title }} </span><a v-if="perikope.URL" :href="perikope.URL"
                   target="_blank">{{ perikope.Bibelstelle }}</a><span v-else>{{ perikope.Bibelstelle }} </span>
            <span v-if="loading" class="mdi mdi-spin mdi-loading"></span>
            <span v-if="!loading" class="mdi mdi-content-copy" @click.prevent.stop="copyToClipboard"
                  title="Klicken, um den Text in die Zwischenablage zu kopieren"></span>
        </div>
    </div>
</template>

<script>
export default {
    name: "BibleReference",
    props: ['perikope', 'title', 'inline'],
    methods: {
        copyToClipboard() {
            const cb = navigator.clipboard;
            cb.writeText(this.text+"\n("+this.reference.correctedReference+')').then(result => {});
        }
    },
    mounted() {
        if(this.perikope?.Bibelstelle) {
            axios.get(route('bible.text', {reference: this.perikope.Bibelstelle}))
                .then(result => {
                    this.text = result.data.text;
                    this.reference = result.data.reference;
                    this.loading = false;
                });
        }
    },
    data() {
        return {
            text: '',
            reference: {},
            loading: true,
        }
    }
}
</script>

<style scoped>
    .bible-reference.bible-reference-inline, .bible-reference.bible-reference-inline div {
        display: inline;
    }

    .mdi.mdi-spin {
        color: lightgray;
    }
    .mdi-content-copy {
        color: lightgray;
        display: none;
    }
    .mdi-content-copy:hover {
        color: gray;
    }

    .bible-reference:hover .mdi-content-copy {
        display: inline;
    }
</style>

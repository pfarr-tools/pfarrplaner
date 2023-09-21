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
    <liturgy-sheet-configuration-form :service="service" :sheet="sheet">
        <tab-headers>
            <tab-header id="colorscheme" active="1" title="Farbschema" />
            <tab-header id="layout" title="Layout" />
            <tab-header id="content" title="Inhalte" />
        </tab-headers>
        <tabs>
            <tab id="colorscheme" active="1">
                <input type="hidden" name="config[backgroundColor]" v-model="myConfig.backgroundColor" />
                <form-selectize label="Hintergrundfarbe für Folien mit Text" v-model="myConfig.backgroundColor"
                                :options="myColorOptions" />
                <input type="hidden" name="config[backgroundColorEmpty]" v-model="myConfig.backgroundColorEmpty" />
                <form-selectize label="Hintergrundfarbe für Folien ohne Text" v-model="myConfig.backgroundColorEmpty"
                                :options="myColorOptions" />
                <input type="hidden" name="config[textColor]" v-model="myConfig.textColor" />
                <form-selectize label="Textfarbe" v-model="myConfig.textColor"
                                :options="myColorOptions" />
                <input type="hidden" name="config[verticalAlignment]" v-model="myConfig.verticalAlignment" />
            </tab>
            <tab id="layout">
                <form-selectize label="Vertikale Ausrichtung der Texte" v-model="myConfig.verticalAlignment"
                                :options="myAlignmentOptions" />
                <form-input label="Schrifgröße" type="number" v-model="myConfig.fontSize" />
            </tab>
            <tab id="content">
                <form-check label="Leere Folien zwischen den Elementen" v-model="myConfig.includeEmpty" name="config[includeEmpty]"/>
                <form-check label="Platzhalter für Jingle, Intro, ..." v-model="myConfig.includeJingleAndIntro" name="config[includeJingleAndIntro]"/>
                <form-check label="Mitwirkende anzeigen" v-model="myConfig.includeCredits" name="config[includeCredits]"/>
                <hr />
                <form-check label="Liederliste am Anfang" v-model="myConfig.includeSongList" name="config[includeSongList]"/>
                <form-check label="Hinweis auf Liederbuch vor jedem Lied" v-model="myConfig.includeSongbookReference" name="config[includeSongbookReference]"/>
                <form-check label="Wo möglich, Noten statt Text verwenden" v-model="myConfig.renderMusic" name="config[renderMusic]" />
            </tab>
        </tabs>
    </liturgy-sheet-configuration-form>
</template>

<script>
import LiturgySheetConfigurationLayout from "./LiturgySheetConfigurationLayout";
import FormCheck from "../../../components/Ui/forms/FormCheck";

export default {
    name: "SongPPTSongSheetConfiguration",
    components: {FormCheck, LiturgySheetConfigurationLayout},
    props: ['service', 'sheetConfig', 'config'],
    data() {
        return {
            myConfig: this.config,
        }
    },
}
</script>

<style scoped>

</style>

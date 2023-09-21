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
    <div class="liturgy-item-freetext-editor">
        <div class="form-group">
            <label for="title">Titel im Ablaufplan</label>
            <input class="form-control" v-model="editedElement.title" v-focus/>
        </div>

        <liturgy-text-editor v-model="editedElement.data.description" settings="myEditorSettings" :service="service"/>
        <text-stats :text="editedElement.data.description"/>
    </div>
</template>

<script>
import Nl2br from 'vue-nl2br';
import TextStats from "../Elements/TextStats";
import FormInput from "../../Ui/forms/FormInput";
import FormSelectize from "../../Ui/forms/FormSelectize";
import NavButton from "../../Ui/buttons/NavButton";
import FormFileUpload from "../../Ui/forms/FormFileUpload";
import {romanize} from "../../../libraries/Romanize";
import FormBibleReferenceInput from "../../Ui/forms/FormBibleReferenceInput";
import ReplacementMenu from "./Elements/ReplacementMenu";
import ReplacementMenuButton from "./Elements/ReplacementMenuButton";
import RelativeDate from "../../../libraries/RelativeDate";
import LiturgyTextEditor from "./Elements/LiturgyTextEditor.vue";

export default {
    name: "FreetextEditor",
    components: {
        LiturgyTextEditor,
        ReplacementMenuButton,
        ReplacementMenu,
        FormBibleReferenceInput, FormFileUpload, NavButton, FormSelectize, FormInput, TextStats, Nl2br
    },
    inject: ['lists'],
    props: {
        element: Object,
        service: Object,
        agendaMode: {
            type: Boolean,
            default: false,
        },
        markers: {
            type: Object,
            default: null,
        },
    },
    data() {
        var e = this.element;
        if (undefined == e.data.description) e.data.description = '';

        let replacementMenus = [];

        this.service.baptisms.forEach(baptism => {
        });
        this.service.funerals.forEach(funeral => {
            let items = {};
            replacementMenus.push({
                title: funeral.buried_name,
                icon: 'mdi mdi-grave-stone',
                show: false,
                items: {
                    'Geburtsdatum': moment(funeral.dob).locale('de').format('LL'),
                    'Sterbedatum': moment(funeral.dod).locale('de').format('LL'),
                    'Sterbedatum (relativ)': RelativeDate(moment(funeral.dod).format('DD.MM.YYYY'), moment(this.service.date).format('DD.MM.YYYY')),
                    'Sterbealter': funeral.age,
                    'Lebenszeit in Tagen': moment(funeral.dod).diff(moment(funeral.dob), 'days').toLocaleString('de-DE'),
                    'Geburtsort': funeral.birth_place,
                    'Sterbeort': funeral.death_place,
                    'Geburtsname': funeral.birth_name,
                    'Rufname': funeral.spoken_name,
                }
            })
        });
        this.service.weddings.forEach(wedding => {
        });


        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            editedElement: e,
            selectedText: '',
            showTextDropdown: false,
            showBibleImportDropdown: false,
            showWordImportDropdown: false,
            insertBibleReference: '',
            replacementMenus,
            myEditorSettings: {},
        };
    },
    methods: {
        insertText(text) {
            var myField = this.$refs['textEditor'];

            if (myField.selectionStart || myField.selectionStart == '0') {
                var startPos = myField.selectionStart;
                var endPos = myField.selectionEnd;
                this.editedElement.data.description = myField.value.substring(0, startPos)
                    + text
                    + myField.value.substring(endPos, myField.value.length);
                myField.focus();
                myField.selectionStart = myField.selectionEnd = myField.selectionEnd + text.length;
            } else {
                this.editedElement.data.description += text;
                myField.focus();
            }
        },
        toggleTextDropdown() {
            this.showTextDropdown = !this.showTextDropdown;
            if (!this.showTextDropdown) this.$refs['textEditor'].focus();
        },
        toggleBibleImportDropdown() {
            this.showBibleImportDropdown = !this.showBibleImportDropdown;
            if (!this.showBibleImportDropdown) this.$refs['textEditor'].focus();
        },
        toggleWordImportDropdown() {
            this.showWordImportDropdown = !this.showWordImportDropdown;
            if (!this.showWordImportDropdown) this.$refs['textEditor'].focus();
        },
        insertBibleText() {
            this.insertText(this.insertBibleReference);
            this.showBibleImportDropdown = false;
            this.insertBibleReference = '';
            this.$refs['textEditor'].focus();
        }
    },
}
</script>

<style scoped>
.liturgy-item-freetext-editor {
    padding: 5px;
}

.help {
    margin-top: .5em;
    margin-bottom: .5em;
    font-size: .8em;
}

.help-title {
    font-weight: bold;
    margin-bottom: .25em;
}

.dropdown-menu {
    width: 100% !important;
}
</style>

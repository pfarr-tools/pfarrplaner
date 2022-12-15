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
    <div class="liturgy-item-freetext-editor">
        <div class="form-group">
            <label for="title">Titel im Ablaufplan</label>
            <input class="form-control" v-model="editedElement.title" v-focus/>
        </div>


        <div class="form-group">
            <label for="description">Beschreibender Text</label>
            <div class="dropdown mb-1">
                <div class="btn-group">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                            @click="toggleTextDropdown"
                            title="Liturgischen Text einfügen">
                        <span class="mdi mdi-text"></span> Liturgischen Text einfügen
                    </button>
                    <button class="btn btn-light dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                            @click="toggleBibleImportDropdown"
                            title="Bibeltext importieren">
                        <span class="mdi mdi-book-open-variant"></span> Bibeltext einfügen
                    </button>
                    <button class="btn btn-light dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                            @click="toggleWordImportDropdown"
                            title="Aus Worddokument importieren">
                        <span class="mdi mdi-file-word"></span>
                    </button>
                    <replacement-menu-button
                        v-for="(item,itemIndex) in replacementMenus" :menu="item" :key="itemIndex"
                        @toggle="item.show = $event" />
                </div>
                <div class="dropdown-menu bg-light" :style="{display: showTextDropdown ? 'block' : 'none'}"
                     aria-labelledby="dropdownMenuButton">
                    <div class="dropdown-form p-1">
                        <div class="row">
                            <div class="col-sm-6">
                                <div v-if="lists.texts.length > 0">
                                    <form-selectize label="Textbaustein" :options="lists.texts"
                                                    title-key="title"
                                                    v-model="selectedText" :key="lists.texts.length"/>
                                </div>
                                <nav-button type="secondary" icon="mdi mdi-text"
                                            @click="insertText(lists.texts.filter(item => item.id == selectedText)[0].text); showTextDropdown = false;"
                                            title="Einfügen">Einfügen
                                </nav-button>
                            </div>
                            <div class="col-sm-6">
                                <nl2br tag="div" v-if="selectedText"
                                       :text="lists.texts.filter(item => item.id == selectedText)[0].text"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dropdown-menu bg-light" :style="{display: showBibleImportDropdown ? 'block' : 'none'}"
                     aria-labelledby="dropdownMenuButton">
                    <div class="dropdown-form p-1">
                        <form-bible-reference-input v-model="insertBibleReference" full-text
                                                    :sources="textSources" :clipboard="false"/>
                        <button v-if="insertBibleReference" class="btn btn-secondary"
                                @click.prevent.stop="insertBibleText">Einfügen
                        </button>
                    </div>
                </div>
                <div class="dropdown-menu bg-light" :style="{display: showWordImportDropdown ? 'block' : 'none'}"
                     aria-labelledby="dropdownMenuButton">
                    <div class="dropdown-form p-1">
                        <form-file-upload @input="upload"
                                          no-url="1" no-pixabay="1" no-camera="1" no-description="1"/>
                    </div>
                </div>
                <replacement-menu
                    v-for="(item,itemIndex) in replacementMenus" :menu="item" :key="itemIndex"
                    @input="item.show=false; insertText($event)" />
            </div>
            <textarea class="form-control" v-model="editedElement.data.description" ref="textEditor"
                      rows="15"></textarea>
            <text-stats :text="editedElement.data.description"/>
        </div>
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

export default {
    name: "FreetextEditor",
    components: {
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

        let textSources = {};
        if (undefined !== this.service.liturgicalInfo.title) {
            textSources['Perikope für ' + this.service.liturgicalInfo.title] = this.service.liturgicalInfo.currentPerikope;
            for (let i = 1; i <= 6; i++) {
                textSources[this.service.liturgicalInfo.title + ' ' + romanize(i)] = this.service.liturgicalInfo['litTextsPerikope' + i];
            }
            textSources[this.service.liturgicalInfo.title + ' Psalm'] = this.service.liturgicalInfo['litTextsWeeklyPsalm'];
            textSources[this.service.liturgicalInfo.title + ' Wochenspruch'] = this.service.liturgicalInfo['litTextsWeeklyQuote'];
        }

        let replacementMenus = [];

        this.service.baptisms.forEach(baptism => {
            if (baptism.text) textSources['Taufspruch ' + baptism.candidate_name] = baptism.text;
        });
        this.service.funerals.forEach(funeral => {
            if (funeral.text) textSources['Beerdigungstext ' + funeral.buried_name] = funeral.text;
            if (funeral.confirmation_text) textSources['Denkspruch ' + funeral.buried_name] = funeral.confirmation_text;
            if (funeral.wedding_text) textSources['Trauspruch ' + funeral.buried_name] = funeral.wedding_text;

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
            if (wedding.text) textSources['Trauspruch ' + wedding.spouse1_name + ' & ' + wedding.spouse2_name] = wedding.text;
        });


        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            editedElement: e,
            selectedText: '',
            showTextDropdown: false,
            showBibleImportDropdown: false,
            showWordImportDropdown: false,
            textSources,
            insertBibleReference: '',
            replacementMenus,
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
        upload(file) {
            let fd = new FormData();
            fd.append('import', file);

            this.uploading = true;
            axios.post(route('api.liturgy.text.import', {
                api_token: this.apiToken,
            }), fd, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.showWordImportDropdown = false;
                this.insertText(response.data);
                this.$refs['textEditor'].focus();
            });
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

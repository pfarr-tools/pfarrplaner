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
    <modal title="Liturgischen Text einfügen" close-button-label="Einfügen"
           @cancel="$emit('input', '')" min-height="50vh"
           @close="$emit('input', myText)">
        <div class="row">
            <div class="col-sm-6">
                <div v-if="lists.texts.length > 0">
                    <form-selectize label="Textbaustein" :options="lists.texts"
                                    title-key="title" :settings="selectizeSettings"
                                    v-model="selectedText" :key="lists.texts.length"/>
                </div>


                <div v-if="myUnfilteredText.includes('[bestattung:')" class="form-group">
                    <label>Platzhalter ersetzen für Beerdigung</label>
                    <select class="form-control" v-model="replacementFuneral">
                        <option v-for="funeral in service.funerals" :value="funeral.id">{{
                                funeral.buried_name
                            }}
                        </option>
                    </select>
                </div>
                <div v-if="myUnfilteredText.includes('[taufe:')" class="form-group">
                    <label>Platzhalter ersetzen für Taufe</label>
                    <select class="form-control" v-model="replacementBaptism">
                        <option v-for="baptism in service.baptisms" :value="baptism.id">{{
                                baptism.candidate_name
                            }}
                        </option>
                    </select>
                </div>
                <div v-if="myUnfilteredText.includes('[trauung:')" class="form-group">
                    <label>Platzhalter ersetzen für Trauung</label>
                    <select class="form-control" v-model="replacementWedding">
                        <option v-for="wedding in service.weddings" :value="wedding.id">{{
                                wedding.spouse1_name
                            }}
                            &amp; {{ wedding.spouse2_name }}
                        </option>
                    </select>
                </div>


            </div>
            <div class="col-sm-6">
                <div v-if="selectedText" v-html="myText" />
            </div>
        </div>
    </modal>
</template>

<script>
import Modal from "../../../Ui/modals/Modal.vue";
import NavButton from "../../../Ui/buttons/NavButton.vue";
import FormSelectize from "../../../Ui/forms/FormSelectize.vue";
import {PronounSetFactory} from "../../../../libraries/PronounSets/PronounSetFactory";
import RelativeDate from "@pfarr.tools/relative-date";
import {NameService} from "../../../../libraries/NameService";

export default {
    name: "InsertLiturgicTextDialog",
    emits: ['input'],
    props: ['service'],
    components: {FormSelectize, NavButton, Modal},
    inject: ['lists'],
    computed: {
        myUnfilteredText() {
            if (isNaN(this.selectedText)) return '';
            let t = this.lists.texts.filter(item => item.id == this.selectedText);
            if (!t) return '';
            if (!t[0]) return '';
            return t[0].text;
        },
        myText() {
            return this.replaceMarkers(this.myUnfilteredText)
        }
    },
    data() {
        return {
            selectedText: '',
            replacementBaptism: this.service.baptisms.length ? this.service.baptisms[0].id : null,
            replacementFuneral: this.service.funerals.length ? this.service.funerals[0].id : null,
            replacementWedding: this.service.weddings.length ? this.service.weddings[0].id : null,
            selectizeSettings: {
                searchField: ['title', 'source', 'data'],
                render: {
                    option:  function(item, escape) {
                        let t= '<div>'+escape(item.title);
                        if (item.source) t+='<div class="text-sm text-muted">'
                            +'<span class="mdi mdi-book"></span> '
                            +escape(item.source).replaceAll("\n", '<br />')+'</div>';
                        return t+'</div>';
                    }
                }
            },
        }
    },
    methods: {
        replaceMarkers(text) {
            if (!text) return '';
            let dataset = {};

            // wedding
            if (this.replacementWedding && text.includes('[trauung:')) {
                let wedding = this.service.weddings.filter(item => item.id == this.replacementWedding)[0];
                let nameSet = [new NameService(wedding.spouse1_name), new NameService(wedding.spouse2_name)];

                let pronounset1 = PronounSetFactory.get(wedding.pronoun_set1);
                let pronounset2 = PronounSetFactory.get(wedding.pronoun_set2);

                dataset = {
                    'trauung:person1:vorname': nameSet[0].first,
                    'trauung:person1:nachname': nameSet[0].last,
                    'trauung::person1:name': nameSet[0].name,
                    'trauung:person2:vorname': nameSet[1].first,
                    'trauung:person2:nachname': nameSet[1].last,
                    'trauung::person2:name': nameSet[1].name,
                    ...pronounset1.replacementSet('trauung:person1'),
                    ...pronounset2.replacementSet('trauung:person2'),
                };
            }

            // funeral
            if (this.replacementFuneral && text.includes('[bestattung:')) {
                let funeral = this.service.funerals.filter(item => item.id == this.replacementFuneral)[0];
                let nameSet = new NameService(funeral.buried_name, funeral.spoken_name || null)

                let pronounset1 = PronounSetFactory.get(funeral.pronoun_set);

                dataset = {
                    'bestattung:vorname': nameSet.first,
                    'bestattung:nachname': nameSet.last,
                    'bestattung:name': nameSet.name,
                    'bestattung:todesdatum': funeral.dod ? moment(funeral.dod).format('DD.MM.YYYY') : null,
                    'bestattung:todesdatum:relativ': funeral.dod ? RelativeDate(moment(funeral.dod), moment(this.service.date)) : null,
                    'bestattung:geburtsdatum': funeral.dob ? moment(funeral.dob).format('DD.MM.YYYY'): null,
                    ...pronounset1.replacementSet('bestattung'),
                };
            }

            // baptism
            if (this.replacementBaptism && text.includes('[taufe:')) {
                let baptism = this.service.baptisms.filter(item => item.id == this.replacementBaptism)[0];
                let nameSet = new NameService(baptism.candidate_name)

                let pronounset1 = PronounSetFactory.get(baptism.pronoun_set);

                dataset = {
                    'taufe:vorname': nameSet.first,
                    'taufe:nachname': nameSet.last,
                    'taufe:name': nameSet.name,
                    ...pronounset1.replacementSet('taufe'),
                };
            }

            // do replace
            for (const key in dataset) {
                if (null != dataset[key]) text = text.replaceAll('['+key+']', dataset[key]);
            }

            return text;

        }
    }
}
</script>

<style scoped>

</style>

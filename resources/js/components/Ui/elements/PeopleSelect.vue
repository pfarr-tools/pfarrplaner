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
    <div class="peopleselect">
        <form-group :id="myId" :name="name" :label="label" :help="help" pre-label="mdi mdi-account">
            <div ref="container">
                <selectize class="form-control" :class="{'is-invalid': error}" :name="name" :id="myId+'Input'"
                           :value="myValue" multiple @input="changed" @blur="editing = false" :settings="settings"
                           :options="people" :disabled="disabled"/>
                <small class="form-text text-muted">Eine oder mehrere Personen (keine Anmerkungen, Notizen,
                    usw.)</small>
            </div>
        </form-group>
        <modal  v-if="showNewPersonModal" @close="closeNewPersonModal"
                :title="(ignoreSearchResults || searchResults.length == 0) ? 'Neue Person anlegen' : 'Person übernehmen'"
                @keydown.esc="cancelNewPersonModal"
               @cancel="cancelNewPersonModal" :allow-close="ignoreSearchResults || (searchResults.length == 0)"
               @shown="newPersonModalShown" close-button-label="Person speichern">
            <div v-if="searchingForPerson" class="searching-for-person text-muted">
                <div>Bitte warte, Person wird in anderen Kirchengemeinden gesucht...</div>
                <span class="mdi mdi-spin mdi-loading"></span>
            </div>
            <div v-else>
                <div v-if="(searchResults.length > 0) && (!ignoreSearchResults)" class="search-results">
                    <p>Folgende Person<span v-if="searchResults.length > 1">en</span> wurde<span v-if="searchResults.length > 1">n</span> bereits im System gefunden</p>
                    <table class="table">
                        <tbody>
                        <tr v-for="(person,personIndex in searchResults">
                            <td>
                                <avatar :username="displayName(person)" :src="person.image" />
                            </td>
                            <td class="text-left">
                                <div class="text-bold">{{ displayName(person, true) }}</div>
                                <div>
                                    <span class="text-sm">in Kirchengemeinde</span><span v-if="person.city_scopes.length > 1">n</span>: <div class="badge bg-light" v-for="city in person.city_scopes">{{ city.name }}</div>
                                </div>
                            </td>
                            <td class="text-right">
                                <nav-button type="primary"
                                            @click="extendPersonScopeAndCloseModal(person)">Diese Person übernehmen</nav-button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <hr />
                    <p>
                        <span v-if="searchResults.length > 1">Die gesuchte Person ist nicht dabei?</span><span v-else>Das ist nicht die gesuchte Person?</span> Dann kannst du hier eine neue Person anlegen:</p>
                    <div class="text-right">
                        <button class="btn btn-warning btn-sm" @click.prevent.stop="setIgnoreSearchResults">Als neue Person anlegen</button>
                    </div>
                </div>
                <div v-if="ignoreSearchResults || (searchResults.length == 0)">
                <form-input name="name" label="Name" v-model="newPerson.name" ref="newPersonName" id="newPersonName"
                            :autofocus="true"/>
                <hr/>
                <div class="row">
                    <div class="col-md-2">
                        <form-input name="title" label="Titel" v-model="newPerson.title"
                                    placeholder="z.B. Pfr."/>
                    </div>
                    <div class="col-md-5">
                        <form-input name="first_name" label="Vorname" v-model="newPerson.first_name"/>
                    </div>
                    <div class="col-md-5">
                        <form-input name="last_name" label="Nachname" v-model="newPerson.last_name"/>
                    </div>
                </div>
                <form-input name="email" label="E-Mailadresse" v-model="newPerson.email" type="email"
                            help="(falls bekannt)"/>
                </div>
            </div>
            <template v-slot:additional-buttons>
                <button class="btn btn-light" v-if="ignoreSearchResults" @click.prevent.stop="ignoreSearchResults = false">&lt; Zurück zu den Suchergebnissen</button>
            </template>
        </modal>
    </div>
</template>

<script>
import FormGroup from "../forms/FormGroup";
import Selectize from "vue2-selectize";
import FormInput from "../forms/FormInput";
import Modal from "../modals/Modal";
import NavButton from "../buttons/NavButton.vue";
import Avatar from 'vue-avatar';

export default {
    name: "PeopleSelect",
    components: {NavButton, Modal, FormInput, FormGroup, Selectize, Avatar},
    props: {
        label: String,
        id: String,
        type: {
            type: String,
            default: 'text',
        },
        name: String,
        value: Array,
        help: String,
        placeholder: String,
        error: String,
        people: Array,
        includeTeamsFromCity: Object,
        disabled: Boolean,
        teams: {
            type: Array,
            default() {
                return []
            },
        },
        city: Object,
    },
    mounted() {
        if (this.myId == '') this.myId = this._uid;
    },
    data() {
        var myValue = [];
        var myPeopleReference = {};
        var myTeamReference = {};
        var myPeople = this.people.filter(person => person.id != this.$page.props.currentUser.data.id);
        myPeople.unshift(this.$page.props.currentUser.data);


        this.value.forEach(function (person) {
            myValue.push(isNaN(person) ? person.id : person);
        });


        // add own user first
        myPeopleReference[this.$page.props.currentUser.data.id] = {
            type: 'mdi mdi-account',
            name: this.$page.props.currentUser.data.name,
            userString: this.$page.props.currentUser.data.name,
            category: 'Ich selbst',
        }

        myPeople.forEach(person => {
            myPeopleReference[person.id] = person;
            person.type = person.type || 'mdi mdi-account';
            if (person.id == this.$page.props.currentUser.data.id) {
                person.category = 'Ich'
            } else {
                person.category = person.category || 'Andere Personen';
            }
            person.userString = person.userString || '';
        });

        this.teams.forEach(team => {
            myTeamReference[team.id] = team;
            let tempTeam = {
                name: team.name,
                id: 'team:' + team.id,
                type: 'mdi mdi-account-multiple',
                category: 'Teams',
                users: team.users,
                userString: ''
            };
            tempTeam.users.forEach(user => tempTeam.userString += user.name + ' ');
            myPeople.push(tempTeam);
        });


        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            component: this,
            createCallback: null,
            myId: this.id || '',
            myValue: myValue,
            myPeople: myPeople,
            myPeopleReference: myPeopleReference,
            editing: false,
            clicked: false,
            personCreated: false,
            showNewPersonModal: false,
            searchingForPerson: false,
            searchResults: [],
            ignoreSearchResults: false,
            newPerson: {
                name: '',
                first_name: '',
                last_name: '',
                title: '',
            },
            myTeamReference: myTeamReference,
            settings: {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'category', 'userString'],
                optgroupField: 'category',
                optgroupLabelField: 'groupName',
                optgroupValueField: 'groupName',
                optgroups: [{groupName: 'Ich'}, {groupName: 'Andere Personen'}, {groupName: 'Teams'}],
                create: this.addPerson,
                options: myPeople,
                render: {
                    option_create: function (data, escape) {
                        return '<div class="create">Neue Person anlegen: <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                    },
                    item: function (item, escape) {
                        if (item.type == 'mdi mdi-account') {
                            return '<div><span class="mdi mdi-account"></span> ' + escape(item.name) + '</div>';
                        } else {
                            let users = [];
                            item.users.forEach(user => {
                                users.push(user.name);
                            });
                            return '<div><span class="mdi mdi-account-multiple"></span> ' + escape(item.name) + ': ' + escape(users.join(', ')) + '</div>';
                        }
                    },
                    option: function (item, escape) {
                        var t = '<div><span class="ms-1 ' + item.type + '"></span> ' + escape(item.name);

                        if (item.type == 'mdi mdi-account-multiple') {
                            t += '<span class="ms-1 badge bg-dark">' + item.users.length + '</span>'
                            if (item.users.length > 0) t += '<div>';
                            item.users.forEach(user => {
                                t += '<span class="ms-1 badge bg-light">' + user.name + '</span>'
                            });
                            if (item.users.length > 0) t += '</div>';
                        }
                        t += '</div>';
                        return t;
                    }
                },
            },
        }
    },
    methods: {
        changed(newVal) {
            var externalValue = [];
            var newVal2 = [];

            newVal.forEach(item => {
                if (isNaN(item) && (item.substr(0, 5) == 'team:')) {
                    this.myTeamReference[item.substr(5)].users.forEach(user => {
                        newVal2.push(user.id);
                    });
                } else {
                    newVal2.push(item);
                }
            });

            newVal2.forEach(item => {
                externalValue.push(this.myPeopleReference[item]);
            })

            this.$emit('input', externalValue);
            this.$emit('count');
            this.myValue = newVal2;
            this.$forceUpdate();
        },
        closeNewPersonModal() {
            var component = this;
            this.showNewPersonModal = false;
            axios.post(route('users.add'), {...this.newPerson, city_id: this.city.id })
                .then(response => {
                    return response.data;
                })
                .then(data => {
                    data.type = 'mdi mdi-account';
                    data.category = 'Personen';
                    data.userString = '';

                    this.myPeople.push(data);
                    this.myPeopleReference[data.id] = data;
                    this.createCallback(data);
                    this.changed(this.myValue);
                    component.personCreated = true;
                });
        },
        cancelNewPersonModal() {
            this.showNewPersonModal = false;
        },
        /**
         * Activate a new person for this city, and pick the person for the select list
         * @param person
         */
        extendPersonScopeAndCloseModal(person) {
            this.showNewPersonModal = false;
            if (this.city) {
                axios.post(route('api.people.activate', {
                    api_token: this.apiToken,
                    user: person.id,
                    city: this.city.id,
                }));
            }
            person.type='mdi mdi-account';
            person.category = 'Personen';
            this.myPeople.push(person);
            this.myPeopleReference[person.id] = person;
            this.createCallback(person);
            this.changed(this.myValue);
            component.personCreated = true;
        },
        newPersonModalShown(ref) {
        },
        displayName(person, showTitle = false) {
            let n = showTitle ? (person.title ? person.title+' ' : '') : '';
            return n + ((person.first_name && person.last_name) ? person.first_name+' '+person.last_name : person.name);
        },
        addPerson(item, callback = null) {
            var tmp, firstName, lastName;
            tmp = item.split(' ');
            firstName = tmp[0];
            lastName = tmp[1];
            this.newPerson = {
                name: item,
                first_name: lastName ? firstName.trim() : '',
                last_name: lastName ? lastName.trim() : firstName,
                title: '',
                email: '',
            };
            this.createCallback = callback;
            this.ignoreSearchResults = false;
            this.searchingForPerson = true;
            this.showNewPersonModal = true;
            this.searchResults = [];
            axios.post(route('api.people.search', {
                api_token: this.apiToken,
                searchString: item,
            })).then(response => {
                console.log('found', response.data);
                this.searchResults = response.data;
                this.searchingForPerson = false;
            });
            return fa
        },
        setIgnoreSearchResults() {
            if (confirm('Willst du wirklich die Suchergebnisse ignorieren und stattdessen eine neue Person anlegen? Das solltest du nur tun, wenn du sicher bist, dass die von dir gemeinte Person nicht in der Liste der Suchergebnisse vorhanden ist.')) {
                this.ignoreSearchResults = true;
            }
        }
    }

}
</script>

<style scoped>
.peopleselect-placeholder {
    width: 100%;
    min-height: 2.2rem;
    border: 1px solid #ced4da;
    border-radius: .25rem;
    padding: .2rem .75rem;
}

.people-badge {
    background-color: #efefef;
    font-size: inherit;
    font-weight: normal;
    margin: 0 3px 3px 0;
}

.searching-for-person {
    min-height: 50vh;
    vertical-align: middle;
    text-align: center;
}

.search-results {
    width: 100%;
    height: 50%;
    overflow-y: scroll;
}

</style>

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
    <admin-layout title="Kalenderverbindung einrichten">
        <template slot="navbar-left">
            <button class="btn btn-primary" @click="saveConnection">
                <span class="d-inline d-md-none mdi mdi-content-save"></span><span
                class="d-none d-md-inline">Speichern</span>
            </button>
            <button class="btn btn-danger ml-1" @click="deleteConnection">
                <span class="d-inline d-md-none mdi mdi-delete"></span><span class="d-none d-md-inline">Löschen</span>
            </button>
        </template>
        <form-input name="title" label="Bezeichnung der Verbindung" v-model="myConnection.title" autofocus/>
        <fieldset>
            <legend>Inhalte</legend>
        </fieldset>
        <div class="row" v-for="(city,key,index) in cities" :key="key">
            <div class="col-md-4 text-bold">{{ city.name }}</div>
            <div class="col-md-8">
                <form-selectize :options="myContentOptions" v-model="myCitiesSync[city.id]['connection_type']"/>
            </div>

        </div>
        <form-selectize name="include_vacations" label="Urlaub eintragen" :options="myVacationOptions"
                        v-model="myConnection.include_vacations"/>
        <form-check name="include_hidden" label="Versteckte Gottesdienste mit einbeziehen"
                    v-model="myConnection.include_hidden"/>
        <form-check name="include_hidden" label="Vorbereitungstermine mit einbeziehen"
                    help="z.B. Taufgespräche, Trauergespräche, Traugespräche"
                    v-model="myConnection.include_alternate"/>
        <form-check name="include_rite_anniversaries" label="Erinnerung an den Jahrestag von Beerdigungen, Trauungen"
                    v-model="myConnection.include_rite_anniversaries"/>
    </admin-layout>
</template>

<script>
import Card from "../../components/Ui/cards/card";
import CardHeader from "../../components/Ui/cards/cardHeader";
import CardBody from "../../components/Ui/cards/cardBody";
import FormInput from "../../components/Ui/forms/FormInput";
import FormSelectize from "../../components/Ui/forms/FormSelectize";
import FormCheck from "../../components/Ui/forms/FormCheck";

export default {
    name: "Setup",
    components: {FormCheck, FormSelectize, FormInput},
    props: ['calendarConnection', 'cities'],
    data() {
        // get cities / pivot data
        var myCitiesSync = {};
        this.cities.forEach(city => {
            myCitiesSync[city.id] = {connection_type: 0};
        });
        this.calendarConnection.cities.forEach(city => {
            myCitiesSync[city.id] = {connection_type: city.pivot.connection_type};
        });

        return {
            myConnection: this.calendarConnection,
            myContentOptions: [
                {id: 0, name: 'keine Einträge'},
                {id: 1, name: 'nur eigene Gottesdienste'},
                {id: 2, name: 'alle Gottesdienste'},
            ],
            myVacationOptions: [
                {id: 0, name: 'keinen Urlaub eintragen'},
                {id: 1, name: 'nur eigenen Urlaub + Vertretungen'},
            ],
            myCitiesSync: myCitiesSync,
        }
    },
    methods: {
        saveConnection() {
            this.myConnection.cities = this.myCitiesSync;
            this.$inertia.patch(route('calendarConnection.update', this.myConnection.id), this.myConnection);
        },
        deleteConnection() {
            this.$inertia.delete(route('calendarConnection.destroy', this.myConnection.id));
        },
    },
}
</script>

<style scoped>

</style>

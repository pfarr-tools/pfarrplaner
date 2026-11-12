<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -
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
    <admin-layout title="Opferplan bearbeiten">
        <template #after-flash>
            <div
                v-if="saving"
                class="alert alert-warning"
            >
                Änderungen werden gespeichert ... <span class="mdi mdi-spin mdi-loading"></span>
            </div>
            <div
                v-if="saved"
                class="alert alert-success"
            >
                <span class="mdi mdi-check"></span> Änderungen wurden automatisch gespeichert.
            </div>
        </template>
        <div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover d-md-table">
                            <thead>
                                <tr>
                                    <th>Gottesdienst</th>
                                    <th>Opferzweck</th>
                                    <th>Anmerkungen</th>
                                    <th>Opferzähler</th>
                                    <th>Betrag</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, rowIndex) in services"
                                    :key="rowIndex"
                                >
                                    <td :class="row.cc ? '' : 'text-muted'">
                                        {{ moment(row.date).format('DD.MM.YYYY') }},
                                        {{ row.timeText }}<br />
                                        {{ row.locationText }}
                                        <div>
                                            <span class="badge bg-secondary">{{
                                                row.city.name
                                            }}</span>
                                        </div>
                                        <div>
                                            <nav-button
                                                type="primary"
                                                icon="mdi mdi-pencil"
                                                force-icon
                                                class="btn-sm"
                                                :key="row.slug"
                                                force-no-text
                                                title="Gottesdienst bearbeiten (in neuem Tab)"
                                                @click="editService(row)"
                                                >Bearbeiten</nav-button
                                            >
                                        </div>
                                    </td>
                                    <td>
                                        <form-input
                                            v-model="row.offering_goal"
                                            @input="serviceChanged(component, row)"
                                        />
                                        <form-radio-group
                                            v-model="row.offering_type"
                                            :items="{
                                                '': 'Eigener Beschluss',
                                                eO: 'Empfohlenes Opfer',
                                                PO: 'Pflichtopfer',
                                            }"
                                            @input="offeringTypeChanged(row, $event)"
                                            class="mt-1"
                                        />
                                    </td>
                                    <td>
                                        <form-input
                                            v-model="row.offering_description"
                                            @input="serviceChanged(component, row)"
                                        />
                                    </td>
                                    <td>
                                        <form-input
                                            v-model="row.offerings_counter1"
                                            @input="serviceChanged(component, row)"
                                        />
                                        <form-input
                                            v-model="row.offerings_counter2"
                                            @input="serviceChanged(component, row)"
                                            class="mt-1"
                                        />
                                    </td>
                                    <td>
                                        <form-input
                                            v-model="row.offering_amount"
                                            @input="serviceChanged(component, row)"
                                        />
                                    </td>
                                </tr>
                                <tr v-if="services.length === 0">
                                    <td
                                        colspan="5"
                                        class="text-center"
                                    >
                                        No results found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </admin-layout>
</template>

<script>
import NavButton from '../../../components/Ui/buttons/NavButton'
import FormInput from '../../../components/Ui/forms/FormInput'
import __ from 'lodash'
import FormRadioGroup from '../../../components/Ui/forms/FormRadioGroup'

export default {
    name: 'Form',
    components: {
        FormRadioGroup,
        FormInput,
        NavButton,
    },
    props: ['services', 'users', 'ministries', 'teams'],
    data() {
        return {
            saving: false,
            saved: false,
            component: this,
        }
    },
    methods: {
        serviceChanged: __.debounce((component, service) => {
            component.saveService(service)
        }, 1000),
        offeringTypeChanged(service, offeringType) {
            service.offering_type = offeringType
            this.saveService(service)
        },
        saveService(service) {
            const data = {
                id: service.id,
                offering_goal: service.offering_goal ?? '',
                offering_type: service.offering_type ?? '',
                offering_description: service.offering_description ?? '',
                offerings_counter1: service.offerings_counter1 ?? '',
                offerings_counter2: service.offerings_counter2 ?? '',
                offering_amount: service.offering_amount ?? '',
            }
            this.saved = false
            this.saving = true
            this.$forceUpdate()

            axios.post(route('inputs.save', 'offerings'), data).then(() => {
                this.saving = false
                this.saved = true
                this.$forceUpdate()
            })
        },
        editService(service) {
            window.open(route('service.edit', service.slug))
        },
    },
}
</script>

<style scoped>
.alert.alert-success {
    animation: fadeOut 5s forwards;
}
</style>

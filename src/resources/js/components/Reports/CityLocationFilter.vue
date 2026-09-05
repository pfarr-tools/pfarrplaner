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
    <div>
        <form-selectize
            :name="cityFieldName"
            :label="cityLabel"
            :options="cities"
            :multiple="multipleCities"
            v-model="myCities"
        />
        <location-select
            :key="locationSelectKey"
            :name="locationFieldName"
            :label="locationLabel"
            :placeholder="locationPlaceholder"
            :locations="filteredLocations"
            :multiple="multipleLocations"
            use-input
            v-model="myLocations"
        />
    </div>
</template>

<script>
import FormSelectize from "../Ui/forms/FormSelectize";
import LocationSelect from "../Ui/elements/LocationSelect";

export default {
    name: "CityLocationFilter",
    components: {LocationSelect, FormSelectize},
    props: {
        cities: {
            type: Array,
            required: true,
        },
        locations: {
            type: Array,
            required: true,
        },
        cityModelValue: { type: null, default: null },
        locationModelValue: { type: null, default: null },
        cityFieldName: {
            type: String,
            default: 'cities[]',
        },
        locationFieldName: {
            type: String,
            default: 'locations[]',
        },
        cityLabel: {
            type: String,
            default: 'Kirchengemeinden',
        },
        locationLabel: {
            type: String,
            default: 'Orte',
        },
        locationPlaceholder: {
            type: String,
            default: 'Leer lassen für alle Orte',
        },
        multipleCities: {
            type: Boolean,
            default: true,
        },
        multipleLocations: {
            type: Boolean,
            default: true,
        },
    },
    emits: ['update:cityModelValue', 'update:locationModelValue'],
    data() {
        return {
            myCities: this.normalizeToArray(this.cityModelValue),
            myLocations: this.normalizeToArray(this.locationModelValue),
        };
    },
    computed: {
        selectedCityIds() {
            return this.normalizeToArray(this.myCities).map(item => parseInt(item, 10)).filter(item => !isNaN(item));
        },
        filteredLocations() {
            if (!this.selectedCityIds.length) return [];
            return (this.locations || []).filter(location => this.selectedCityIds.includes(parseInt(location.city_id, 10)));
        },
        filteredLocationIds() {
            return this.filteredLocations.map(location => parseInt(location.id, 10)).filter(id => !isNaN(id));
        },
        locationSelectKey() {
            return `${this.selectedCityIds.join('-')}::${this.filteredLocationIds.join('-')}`;
        },
    },
    watch: {
        myCities: {
            deep: true,
            handler(newVal) {
                this.pruneLocations();
                const normalizedValue = this.normalizeToArray(newVal);

                if (!this.areArraysEqual(normalizedValue, this.normalizeToArray(this.cityModelValue))) {
                    this.$emit('update:cityModelValue', normalizedValue);
                }
            }
        },
        myLocations: {
            deep: true,
            handler(newVal) {
                const normalizedValue = this.normalizeToArray(newVal);

                if (!this.areArraysEqual(normalizedValue, this.normalizeToArray(this.locationModelValue))) {
                    this.$emit('update:locationModelValue', normalizedValue);
                }
            }
        },
        cityModelValue(newVal) {
            const normalizedValue = this.normalizeToArray(newVal);

            if (!this.areArraysEqual(this.normalizeToArray(this.myCities), normalizedValue)) {
                this.myCities = normalizedValue;
            }
        },
        locationModelValue(newVal) {
            const normalizedValue = this.normalizeToArray(newVal);

            if (!this.areArraysEqual(this.normalizeToArray(this.myLocations), normalizedValue)) {
                this.myLocations = normalizedValue;
            }
        }
    },
    methods: {
        normalizeToArray(value) {
            if (Array.isArray(value)) return [...value];
            if ((value === null) || (value === undefined) || (value === '')) return [];
            return [value];
        },
        areArraysEqual(left, right) {
            if (left.length !== right.length) return false;

            return left.every((item, index) => item === right[index]);
        },
        pruneLocations() {
            const prunedLocations = this.normalizeToArray(this.myLocations).filter(locationId => {
                return this.filteredLocationIds.includes(parseInt(locationId, 10));
            });

            if (!this.areArraysEqual(this.normalizeToArray(this.myLocations), prunedLocations)) {
                this.myLocations = prunedLocations;
            }
        },
    }
}
</script>

<style scoped>

</style>

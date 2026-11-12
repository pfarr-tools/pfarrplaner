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
    <admin-layout
        title="Assistenten wählen"
        no-outer-padding
    >
        <FormatIndex
            search-placeholder="Assistenten suchen ..."
            empty-title="Keine Assistenten verfügbar"
            empty-description="Für dein Konto sind derzeit keine freigeschalteten Assistenten verfügbar."
            :groups="formatGroups"
            @select="createInput"
        />
    </admin-layout>
</template>

<script>
import FormatIndex from '../../components/Ui/FormatIndex.vue'

export default {
    name: 'Index',
    components: {
        FormatIndex,
    },
    props: {
        inputs: {
            type: [Array, Object],
            required: true,
        },
    },
    computed: {
        inputList() {
            return Array.isArray(this.inputs) ? this.inputs : Object.values(this.inputs)
        },
        formatGroups() {
            return [
                {
                    title: 'Assistenten',
                    items: this.inputList.map((input) => ({
                        key: input.id,
                        title: input.name,
                        description: input.description,
                        icon: 'mdi mdi-keyboard',
                        href: window.route('inputs.setup', input.id),
                        inertia: true,
                    })),
                },
            ]
        },
    },
    methods: {
        /**
         * Opens an input assistant through Inertia.
         *
         * @param {Object} input The selected input assistant.
         * @returns {void}
         */
        createInput(input) {
            this.$inertia.get(input.href)
        },
    },
}
</script>

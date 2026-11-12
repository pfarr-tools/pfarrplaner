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
        title="Ausgabeformat wählen"
        no-outer-padding
    >
        <FormatIndex
            search-placeholder="Ausgabeformate suchen ..."
            empty-title="Keine Ausgabeformate verfügbar"
            empty-description="Für deine aktuelle Auswahl stehen keine Ausgabeformate zur Verfügung."
            :groups="formatGroups"
            @select="createReport"
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
        reports: {
            type: Object,
            required: true,
        },
    },
    computed: {
        formatGroups() {
            return Object.entries(this.reports).map(([title, groupReports]) => ({
                title,
                items: groupReports.map((report) => ({
                    key: report.key,
                    title: report.title,
                    description: report.description,
                    icon: report.icon,
                    href: window.route('reports.setup', report.key),
                    inertia: report.inertia,
                })),
            }))
        },
    },
    methods: {
        /**
         * Opens a report using its configured navigation mode.
         *
         * @param {Object} report The selected report.
         * @returns {void}
         */
        createReport(report) {
            if (report.inertia) {
                this.$inertia.get(report.href)
            }
        },
    },
}
</script>

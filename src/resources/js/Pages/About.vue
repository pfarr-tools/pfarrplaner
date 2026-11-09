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
    <admin-layout :title="'Über ' + appName">
        <div>
            <h2>{{ appName }}</h2>
            <br />
            <span v-if="appName != 'Pfarrplaner'">Pfarrplaner </span>v.{{ version }}-{{ env }} vom
            {{ moment(date).locale('de').format('LLLL') }}<br />
            Laravel {{ laravelVersion }} auf PHP {{ phpVersion }}
        </div>
        <p v-if="officialServer">
            Gehostet auf einem Server des
            <a
                href="https://wwww.kirchenbezirk-balingen.de/"
                target="_blank"
                >Evangelischen Kirchenbezirks Balingen</a
            >.
        </p>
        <p>
            Der Quellcode von Pfarrplaner ist als Open Source auf
            <a
                :href="repositoryUrl"
                target="_blank"
                >GitHub</a
            >
            verfügbar und steht unter der
            <a
                :href="repositoryUrl + '/blob/main/LICENSE'"
                target="_blank"
                >GNU General Public License (GPL) 3.0</a
            >
            oder höher.
        </p>
        <h3>Container und Dienste</h3>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Dienst</th>
                    <th>Image</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(service, name) in runtime.services"
                    :key="name"
                >
                    <td>{{ name }}</td>
                    <td>
                        <code>{{ service.image }}</code>
                    </td>
                    <td>{{ service.status }}</td>
                </tr>
            </tbody>
        </table>
        <donation-notice class="mb-4" />
        <hr class="my-3" />
        <vue-markdown
            class="changelog"
            :source="changelog"
        />
    </admin-layout>
</template>

<script>
import CardHeader from '../components/Ui/cards/cardHeader'
import CardBody from '../components/Ui/cards/cardBody'
import Card from '../components/Ui/cards/card'
import DonationNotice from '../components/Ui/DonationNotice.vue'
import VueMarkdown from 'vue-markdown-render'

export default {
    name: 'About',
    components: { Card, CardBody, CardHeader, DonationNotice, VueMarkdown },

    props: [
        'version',
        'date',
        'changelog',
        'env',
        'phpVersion',
        'laravelVersion',
        'appName',
        'repositoryUrl',
        'runtime',
    ],
    computed: {
        officialServer() {
            return window.location.href.includes('.pfarrplaner.de')
        },
    },
}
</script>

<style scoped>
.changelog :deep(h1) {
    font-size: 2rem;
}

.changelog :deep(h2),
.changelog :deep(h3) {
    font-size: 1em;
    font-weight: bold;
}
</style>

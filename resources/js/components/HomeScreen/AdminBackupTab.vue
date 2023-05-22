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
    <div class="admin-backup-tab">
        <h3>Backup Status</h3>
        <fake-table :headers="['Ziel', 'Erreichbar', 'Gesund', 'Anzahl Backups', 'Neuestes Backup', 'Benötigter Speicherplatz']"
                    :columns="[2,2,2,2,2,2]" collapsed-header="Backups">
            <div class="row" v-for="backup in backups">
                <div class="col-md-2"><checked-process-item :check="backup.diskCheck" :positive="backup.disk" :negative="backup.disk"/></div>
                <div class="col-md-2"><checked-process-item :check="backup.reachable" positive="online" negative="offline"/></div>
                <div class="col-md-2"><checked-process-item :check="backup.healthy" positive="gesund" negative="ungesund"/></div>
                <div class="col-md-2">{{ backup.amount }}</div>
                <div class="col-md-2">{{ backup.newest }}</div>
                <div class="col-md-2">{{ backup.usedStorage }}</div>
            </div>
        </fake-table>
    </div>
</template>

<script>
import FakeTable from "../Ui/FakeTable";
import CheckedProcessItem from "../Ui/elements/CheckedProcessItem";

export default {
    name: "AdminBackupTab",
    components: {CheckedProcessItem, FakeTable, },
    props: ['backups'],
    data() {
        return {
        }
    },
}
</script>

<style scoped>

</style>

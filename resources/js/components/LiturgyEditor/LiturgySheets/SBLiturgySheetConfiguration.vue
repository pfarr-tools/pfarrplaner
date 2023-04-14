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
    <liturgy-sheet-configuration-form :service="service" :sheet="sheet">
        <form-check label="Platzhalter für Jingle, Intro, ..." v-model="myConfig.includeJingleAndIntro"
                    name="config[includeJingleAndIntro]"/>
        <form-check label="Mitwirkende anzeigen" v-model="myConfig.includeCredits" name="config[includeCredits]"/>
        <form-check label="Liederliste am Anfang" v-model="myConfig.includeSongList" name="config[includeSongList]"/>
    </liturgy-sheet-configuration-form>
</template>

<script>
import LiturgySheetConfigurationForm from "./LiturgySheetConfigurationForm";
import FormCheck from "../../Ui/forms/FormCheck";
import FormSelectize from "../../Ui/forms/FormSelectize";
import FormInput from "../../Ui/forms/FormInput";
import Tabs from "../../Ui/tabs/tabs";
import Tab from "../../Ui/tabs/tab";
import TabHeaders from "../../Ui/tabs/tabHeaders";
import TabHeader from "../../Ui/tabs/tabHeader";
export default {
    name: "SBLiturgySheetConfiguration",
    components: {TabHeader, TabHeaders, Tab, Tabs, FormInput, FormSelectize, FormCheck, LiturgySheetConfigurationForm},
    props: ['service', 'sheet'],
    data() {
        return {
            myConfig: this.sheet.config,
        }
    }
}
</script>

<style scoped>

</style>

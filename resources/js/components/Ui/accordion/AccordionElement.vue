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
    <card>
        <card-header :id="'heading'+uid" @click="open = !open">
            <h2 class="mb-0">
                <button class="btn btn-block text-left pl-0 accordion-button" :class="{ collapsed : !open}" type="button" data-bs-toggle="collapse" :data-target="'#collapse'+uid" aria-expanded="false" :aria-controls="'collapse'+uid"
                @click="open = !open">
                    <span v-if="icon" :class="icon"></span>
                    {{ title }}
                </button>
            </h2>        </card-header>
        <div :id="'collapse'+uid" class="collapse" :class="{ show: open}" :aria-labelledby="'heading'+uid" :data-parent="'#'+accordionId">
        <card-body>
            <slot />
        </card-body>
        </div>
    </card>
</template>

<script>
import Card from "../cards/card";
import CardHeader from "../cards/cardHeader";
import CardBody from "../cards/cardBody";
export default {
    name: "AccordionElement",
    props: ['title', 'icon'],
    components: {CardBody, CardHeader, Card},
    inject: ['accordionId'],
    data() {
        return {
            accordionId: this.accordionId,
            open: false,
            uid: this._uid,
        }
    }
}
</script>

<style scoped>
.btn.accordion-button {
    background-color: transparent !important;
    border-width: 0 !important;
}

.btn.accordion-button:focus {
    box-shadow: none;
}

</style>

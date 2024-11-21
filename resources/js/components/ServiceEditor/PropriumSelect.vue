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

<script>
import FormSelectize from "../Ui/forms/FormSelectize.vue";

export default {
    name: "PropriumSelect",
    components: {FormSelectize},
    props: ['liturgyInfo', 'value', 'label'],
    data() {
        let myItems = this.liturgyInfo;
        for (const myItemKey in myItems) {
            myItems[myItemKey]['searchDate'] = moment(myItems[myItemKey]['date']).format('DD.MM.YYYY');
        }

        return {
            myValue: this.value,
            myItems,
            settings: {
                valueField: 'id',
                labelField: 'title',
                searchField: ['title', 'searchDate'],
                render: {
                    item(item, escape) {
                        var t = '<div class="proprium-item"> <div style="background-color: '+item.litColor+'" class="liturgy-color"></div>'
                        +' '+item.title+' <span class="text-muted">'+item.searchDate+'</span>'
                        t += '</div>';
                        return t;
                    },
                    option(item, escape) {
                        var t = '<div class="proprium-option">&nbsp;<div style="background-color: '+item.litColor+'" class="liturgy-color"></div>'
                        +' '+item.title+' <span class="text-muted">'+item.searchDate+'</span>'
                        t += '</div>';
                        return t;
                    }

                }
            },

        }
    }
}
</script>

<template>
    <form-selectize :label="label" :options="myItems" :settings="settings"
                    v-model="myValue" @input="myValue=$event; $emit('input', $event)" />
</template>

<style scoped>
>>> .liturgy-color {
    display: inline-block;
    border: solid 1px gray;
    min-width: 10px;
    min-height: 10px;
     border-radius: 0;
     border-radius: 0;
}
>>> .liturgy-color.white {
    background-color: white;
    border-color: darkgray;
}
>>> .liturgy-color.black {
    background-color:black;
}
>>> .liturgy-color.green {
    background-color: darkgreen;
}
>>> .liturgy-color.purple {
    background-color: rebeccapurple;
}

>>> .proprium-option .text-muted, >>> .proprium-item .text-muted {
    font-size: .8em;
}

</style>

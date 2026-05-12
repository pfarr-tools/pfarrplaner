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
    <div class="tag-select"  :key="tagsUpdated">
        <form-selectize :name="name" :label="label" :help="help"
                        :options="tags" :value="myValue"
                        :item-renderer="renderOption" :option-renderer="renderOption"
                        @input="handleInput" :settings="mySelectizeSettings"
                        multiple />
    </div>
</template>

<script>
import FormSelectize from "../forms/FormSelectize";
export default {
    name: "TagSelect",
    emits: ['update:modelValue'],
    components: {FormSelectize},
    props: ['tags', 'name', 'label', 'help', 'modelValue', 'return'],
    data() {
        var myValue = [];
        if (this.modelValue) {
            this.modelValue.forEach(item => { myValue.push(item.id)});
        }

        let myTags = this.tags;
        let myReturnProperty = this.return || '';

        return {
            myValue: myValue,
            mySelectizeSettings: {
                options: myTags,
                create: this.addTag,
                render: {
                    option_create: function (data, escape) {
                        return '<div class="create">Neue Kennzeichnung anlegen: <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                    },
                },
            },
            myReturnProperty,
            tagsUpdated: 0,
        }
    },
    methods: {
        renderOption(item, escape) {
            return '<div class="item" style="padding-left: 3px;"><span class="mdi mdi-tag"></span> '+escape(item.name)+'</div>';
        },
        handleInput(e) {
            this.myValue = e;
            var items = [];
            if (!this.myReturnProperty) {
                this.tags.forEach(tag => { if (e.includes(tag.id.toString())) items.push(tag); });
            } else {
                this.tags.forEach(tag => { if (e.includes(tag.id.toString())) items.push(tag[this.myReturnProperty]); });
            }
            this.$emit('update:modelValue', items);
        },
        addTag(e) {
            this.$api().post(route('api.tags.store'), {name: e}).then(response => {
                this.mySelectizeSettings.options.push(response.data);
                this.myValue.push(response.data.id);
                this.tagsUpdated++;
                this.$forceUpdate();
                this.handleInput(this.myValue);
            });
            return {name: e};
        }
    }
}
</script>

<style scoped>

</style>

<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
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
    <modal title="Worddokument einfügen" close-button-label="Abbrechen"
           :allow-cancel="false" submit-button-type="secondary"
           @cancel="$emit('input', '')"
           @close="$emit('input', '')">
        <form-file-upload @input="upload"
                          no-url="1" no-pixabay="1" no-camera="1" no-description="1"/>
    </modal>
</template>

<script>
import Modal from "../../../Ui/modals/Modal.vue";
import {romanize} from "../../../../libraries/Romanize";
import FormFileUpload from "../../../Ui/forms/FormFileUpload.vue";

export default {
    name: "InsertWordDocumentDialog",
    components: {FormFileUpload, Modal},
    data() {
        return {
            apiToken: this.$page.props.currentUser.data.api_token,
            insertBibleReference: '',
        }
    },
    methods: {
        upload(file) {
            let fd = new FormData();
            fd.append('import', file);

            this.uploading = true;
            axios.post(route('api.liturgy.text.import', {
                api_token: this.apiToken,
            }), fd, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                this.$emit('input', response.data);
            });
        },
    }
}
</script>

<style scoped>

</style>

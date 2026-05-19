
<template>
    <admin-layout title="Suche nach Organist:innen">
        <template v-slot:navbar-left>
            <save-button label="Erstellen" title="Suche nach Organist:innen" @click="renderReport" />
        </template>
        <form method="post" :action="route('reports.render', {report: 'missingOrganists'})" @submit.prevent="renderReport">
            <form-csrf-token />
            <form-selectize name="cities[]" label="Organist:innen für folgende Kirchengemeinden erstellen" :options="cities" v-model="myCities" multiple />
            <form-date-range-picker label="Gottesdienste von" v-model:from="myStart" v-model:to="myEnd" iso-date />
        </form>
    </admin-layout>
</template>

<script>
import SaveButton from "../../../components/Ui/buttons/SaveButton";
import FormSelectize from "../../../components/Ui/forms/FormSelectize";
import FormCsrfToken from "../../../components/Ui/forms/FormCsrfToken";
import FormInput from "../../../components/Ui/forms/FormInput";
import FormDateRangePicker from "../../../components/Ui/forms/FormDateRangePicker";
import { submitReportForm } from "../../../helpers/submitReportForm";
export default {
    name: "Setup",
    props: ['cities'],
    components: {FormDateRangePicker, FormInput, FormCsrfToken, FormSelectize, SaveButton},
    data() {
        return {
            myUser: this.$page.props.currentUser.data.id,
            myStart: moment(),
            myEnd: moment().endOf('year'),
            myCities: this.cities.length > 0 ? [this.cities[0].id] : [],
        }
    },
    methods: {
        renderReport() {
            submitReportForm(route('reports.render', {report: 'missingOrganists'}), {
                cities: this.myCities,
                start: this.myStart,
                end: this.myEnd,
            });
        },
    }
}
</script>

<style scoped>

</style>

<template>
    <div class="predicant-services-tab">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>{{ title }}</h2>
            <form-selectize label="Gemeinden" :options="cities" :multiple="true"
                            v-model="selectedCities" @update:model-value="updateCities" />
        </div>

        <div v-if="filteredServices.length === 0" class="alert alert-info">
            Es sind keine offenen Gottesdienste vorhanden.
        </div>

        <fake-table v-else :columns="[3, 5, 2, 2]"
                    :headers="['Gottesdienst', 'Details', 'Gemeinde', '']"
                    collapsed-header="Offene Gottesdienste">
            <div v-for="(service, serviceIndex) in filteredServices" :key="service.id"
                 class="row mb-3 p-1" :class="{'stripe-odd': serviceIndex % 2 === 0}">
                <div class="col-md-3"><basic-info :service="service" /></div>
                <div class="col-md-5"><details-info :service="service" /></div>
                <div class="col-md-2">{{ service.city?.name }}</div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary" :disabled="claiming === service.id"
                            title="Gottesdienst übernehmen" @click="claim(service)">
                        <span class="mdi mdi-account-check"></span> Übernehmen
                    </button>
                </div>
            </div>
        </fake-table>
    </div>
</template>

<script>
import BasicInfo from '../Service/BasicInfo'
import DetailsInfo from '../Service/DetailsInfo'
import FakeTable from '../Ui/FakeTable'
import FormSelectize from '../Ui/forms/FormSelectize'

export default {
    name: 'PredicantServicesTab',
    components: { BasicInfo, DetailsInfo, FakeTable, FormSelectize },
    props: ['title', 'description', 'user', 'settings', 'services', 'cities', 'config'],
    data() {
        return {
            selectedCities: (this.settings?.predicantServicesCities || []).map(Number),
            claiming: null,
        }
    },
    computed: {
        filteredServices() {
            if (!this.selectedCities.length) return this.services
            return this.services.filter(service => this.selectedCities.includes(service.city_id))
        },
    },
    methods: {
        async updateCities(cities) {
            this.selectedCities = (cities || []).map(Number)
            this.settings.predicantServicesCities = this.selectedCities
            await this.$api().post(route('setting.set', { user: this.user.id, key: 'predicantServicesCities' }), {
                value: this.selectedCities,
            })
        },
        async claim(service) {
            this.claiming = service.id
            try {
                await this.$api().post(route('api.service.claimPredicant', { service: service.id }))
                const index = this.services.findIndex(item => item.id === service.id)
                if (index !== -1) this.services.splice(index, 1)
            } finally {
                this.claiming = null
            }
        },
    },
}
</script>

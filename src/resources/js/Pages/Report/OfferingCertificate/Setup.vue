<template>
    <admin-layout title="Opferurkunde">
        <template v-slot:navbar-left>
            <save-button
                label="Erstellen"
                title="Opferurkunde erstellen"
                @click="renderReport"
            />
        </template>
        <div class="container py-3">
            <h1>Opferurkunde</h1>
            <p>Wählen Sie einen Gottesdienst mit erfasster Opfersumme aus.</p>
            <form
                ref="reportForm"
                method="get"
                :action="route('reports.render.get', { report: 'offeringCertificate' })"
            >
                <div class="list-group">
                    <label
                        v-for="service in services"
                        :key="service.id"
                        class="list-group-item"
                    >
                        <input
                            class="form-check-input me-2"
                            name="service"
                            type="radio"
                            :value="service.id"
                            required
                        />
                        {{ service.dateText }} – {{ service.timeText }} – {{ service.city.name }} –
                        {{ service.titleText }}
                    </label>
                </div>
            </form>
        </div>
    </admin-layout>
</template>

<script>
import SaveButton from '../../../components/Ui/buttons/SaveButton'

/**
 * Select a recorded service for the offering certificate report.
 */
export default {
    name: 'OfferingCertificateSetup',
    components: { SaveButton },
    props: {
        services: { type: Array, default: () => [] },
    },
    methods: {
        /**
         * Submit the selected service to the certificate renderer.
         *
         * @returns {void}
         */
        renderReport() {
            this.$refs.reportForm.submit()
        },
    },
}
</script>

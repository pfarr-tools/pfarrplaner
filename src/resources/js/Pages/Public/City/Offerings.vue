<template>
    <main class="container py-3 offerings-page">
        <header class="mb-4 text-center">
            <h1>{{ city.official_name || city.name }}</h1>
            <p class="lead mb-0">Opfer erfassen</p>
        </header>

        <div
            v-if="fallback"
            class="alert alert-info"
        >
            Heute finden keine Gottesdienste statt. Bitte wählen Sie einen Gottesdienst in der
            näheren Umgebung aus.
        </div>

        <section
            v-if="showConfirmation"
            class="alert alert-success text-center"
            data-testid="save-confirmation"
        >
            <h2 class="h4">Opfer erfolgreich gespeichert</h2>
            <p class="mb-3">{{ successMessage }}</p>
            <button
                type="button"
                class="btn btn-success"
                data-testid="continue-entry"
                @click="continueEntry"
            >
                Weitere Opfer erfassen
            </button>
        </section>

        <div
            v-else-if="!selectedService"
            data-testid="service-selection-view"
        >
            <section
                v-for="service in serviceSelectionList"
                :key="service.id"
                class="card mb-3 service-card"
            >
                <div class="card-body">
                    <button
                        type="button"
                        class="btn btn-link service-selector p-0 text-start"
                        data-testid="service-selector"
                        @click="selectService(service)"
                    >
                        <span class="service-date">{{ formatServiceDate(service.date) }}</span>
                        <span class="service-time">{{ service.time }}</span>
                        <span class="service-title">{{ service.title }}</span>
                        <span class="service-location">{{ service.location }}</span>
                    </button>
                    <p
                        v-if="service.offering_goal"
                        class="text-muted mt-2 mb-0"
                    >
                        Opferzweck: {{ service.offering_goal }}
                    </p>
                </div>
            </section>
            <button
                v-if="availableNearbyServices.length"
                type="button"
                class="btn btn-link px-0"
                data-testid="nearby-services-toggle"
                :aria-expanded="nearbyServicesExpanded"
                @click="nearbyServicesExpanded = !nearbyServicesExpanded"
            >
                {{
                    nearbyServicesExpanded
                        ? 'Weitere Gottesdienste ausblenden'
                        : 'Weitere Gottesdienste in der Umgebung anzeigen'
                }}
            </button>
        </div>

        <section
            v-else
            data-testid="offering-entry-view"
        >
            <button
                type="button"
                class="btn btn-link px-0 mb-3"
                data-testid="back-to-services"
                @click="backToServices"
            >
                ← Anderen Gottesdienst auswählen
            </button>
            <form
                class="card offering-form"
                @submit.prevent="saveOffering"
            >
                <div class="card-body">
                    <h2 class="h4">{{ selectedService.title }}</h2>

                    <label
                        class="form-label"
                        for="offering-amount"
                        >Gesamtsumme in Euro</label
                    >
                    <div class="input-group mb-3">
                        <input
                            id="offering-amount"
                            data-testid="offering-amount"
                            v-model="form.offering_amount"
                            class="form-control form-control-lg"
                            inputmode="decimal"
                            placeholder="0,00"
                            :readonly="denominationsExpanded && calculatedTotalCents > 0"
                        />
                        <span class="input-group-text">€</span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-secondary mb-3"
                        data-testid="toggle-denominations"
                        @click="toggleDenominations"
                    >
                        <span class="mdi mdi-cash-multiple me-1"></span>
                        {{
                            denominationsExpanded
                                ? 'Einzelaufstellung ausblenden'
                                : 'Einzelaufstellung einblenden'
                        }}
                    </button>

                    <div
                        v-if="denominationsExpanded"
                        data-testid="denomination-editor"
                        class="denomination-editor border rounded p-2 mb-3"
                    >
                        <div
                            v-for="(label, cents) in denominations"
                            :key="cents"
                            class="row align-items-center mb-2"
                        >
                            <label
                                class="col form-label mb-0"
                                :for="`denomination-${cents}`"
                                >{{ label }}</label
                            >
                            <div class="col-4 col-sm-3">
                                <input
                                    :id="`denomination-${cents}`"
                                    :data-denomination="cents"
                                    v-model.number="form.offering_denominations[cents]"
                                    class="form-control"
                                    min="0"
                                    type="number"
                                />
                            </div>
                        </div>
                        <div
                            class="border-top pt-2 text-end fw-bold"
                            data-testid="calculated-total"
                        >
                            Summe: {{ formatAmount(calculatedTotalCents) }} €
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label
                                class="form-label"
                                for="offerings-counter1"
                                >1. Opferzähler:in</label
                            >
                            <input
                                id="offerings-counter1"
                                v-model="form.offerings_counter1"
                                class="form-control"
                            />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label
                                class="form-label"
                                for="offerings-counter2"
                                >2. Opferzähler:in</label
                            >
                            <input
                                id="offerings-counter2"
                                v-model="form.offerings_counter2"
                                class="form-control"
                            />
                        </div>
                    </div>

                    <div
                        v-if="electronicSignatures && signatureEntries.length"
                        class="mt-4"
                        data-testid="signature-section"
                    >
                        <p
                            class="alert alert-info"
                            data-testid="signature-confirmation"
                        >
                            Wir bestätigen mit unseren Unterschriften die Richtigkeit der
                            vorstehenden Opferabrechnung.
                        </p>
                        <div
                            v-for="entry in signatureEntries"
                            :key="entry.index"
                            class="mb-3"
                            data-testid="signature-field"
                        >
                            <SignaturePad
                                v-model="form.signatures[entry.index]"
                                :label="entry.name"
                                :required="true"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg w-100"
                        data-testid="save-offering"
                        :disabled="electronicSignatures && !hasAllSignatures"
                    >
                        Opfer speichern
                    </button>
                </div>
            </form>
        </section>
    </main>
</template>

<script>
import { formatPlannerDateForDisplay } from '../../../helpers/plannerDates'
import SignaturePad from '../../../Components/Forms/SignaturePad.vue'

/**
 * Create a fresh empty array for nearby services.
 *
 * @returns {Array} Empty nearby service list.
 */
function defaultNearbyServices() {
    return []
}

/**
 * Mobile public page for recording offerings through a signed city link.
 */
export default {
    name: 'PublicCityOfferings',
    components: { SignaturePad },
    props: {
        city: { type: Object, required: true },
        services: { type: Array, default: () => [] },
        nearbyServices: { type: Array, default: defaultNearbyServices },
        fallback: { type: Boolean, default: false },
        denominations: { type: Object, default: () => ({}) },
        electronicSignatures: { type: Boolean, default: false },
    },
    data() {
        return {
            denominationsExpanded: false,
            confirmationDismissed: false,
            nearbyServicesExpanded: false,
            form: this.createForm(),
            selectedServiceId: null,
        }
    },
    computed: {
        /**
         * Return the selected service record.
         *
         * @returns {Object|undefined} Selected service.
         */
        selectedService() {
            return [...this.services, ...this.nearbyServices].find(
                (service) => service.id === this.selectedServiceId,
            )
        },
        /**
         * Return services without a previously recorded offering amount.
         *
         * @returns {Array} Services that can still be entered.
         */
        availableServices() {
            return this.services.filter((service) => !service.offering_amount)
        },
        /**
         * Return nearby services without a previously recorded offering amount.
         *
         * @returns {Array} Additional services that can still be entered.
         */
        availableNearbyServices() {
            return this.nearbyServices.filter((service) => !service.offering_amount)
        },
        /**
         * Return the current and optionally expanded nearby service list.
         *
         * @returns {Array} Services visible in the selection list.
         */
        serviceSelectionList() {
            return this.nearbyServicesExpanded
                ? [...this.availableServices, ...this.availableNearbyServices]
                : this.availableServices
        },
        /**
         * Calculate the denomination total in cents.
         *
         * @returns {number} Total cents.
         */
        calculatedTotalCents() {
            return Object.entries(this.form.offering_denominations).reduce(
                (total, [cents, count]) => total + Number(cents) * (Number(count) || 0),
                0,
            )
        },
        /**
         * Return named counters that need an electronic signature.
         *
         * @returns {Array<{index: number, name: string}>} Signers.
         */
        signatureEntries() {
            return [this.form.offerings_counter1, this.form.offerings_counter2]
                .map((name, index) => ({ name: String(name || '').trim(), index }))
                .filter(({ name }) => name !== '')
        },
        /**
         * Determine whether every named counter has signed.
         *
         * @returns {boolean} True when all required signatures exist.
         */
        hasAllSignatures() {
            return (
                this.signatureEntries.length > 0 &&
                this.signatureEntries.every(({ index }) => this.form.signatures[index])
            )
        },
        /**
         * Identify changes that invalidate collected signatures.
         *
         * @returns {string} Stable key for certificate data.
         */
        signableDataKey() {
            return JSON.stringify({
                amount: this.form.offering_amount,
                denominations: this.form.offering_denominations,
                counter1: this.form.offerings_counter1,
                counter2: this.form.offerings_counter2,
            })
        },
        /**
         * Return the success message shared after a saved offering redirect.
         *
         * @returns {string|null} Success message, if present.
         */
        successMessage() {
            return this.$page?.props?.flash?.success ?? null
        },
        /**
         * Determine whether the save confirmation should be displayed.
         *
         * @returns {boolean} True while the confirmation is visible.
         */
        showConfirmation() {
            return Boolean(this.successMessage) && !this.confirmationDismissed
        },
    },
    watch: {
        signableDataKey() {
            if (this.form.signatures.some(Boolean)) this.form.signatures = ['', '']
        },
        calculatedTotalCents(total) {
            if (this.denominationsExpanded) {
                this.form.offering_amount = total > 0 ? this.formatAmount(total) : ''
            }
        },
    },
    methods: {
        /**
         * Format the service date for the public service selector.
         *
         * @param {string} date ISO date from the public offerings payload.
         * @returns {string} Date formatted for German users.
         */
        formatServiceDate(date) {
            return formatPlannerDateForDisplay(date)
        },
        /**
         * Build editable form data from a service.
         *
         * @param {Object|undefined} service Service data.
         * @returns {Object} Editable form data.
         */
        createForm(service) {
            const counts = {}
            Object.keys(this.denominations).forEach((cents) => {
                counts[cents] = service?.offering_denominations?.[cents] ?? 0
            })
            return {
                offering_amount: service?.offering_amount ?? '',
                offering_denominations: counts,
                offerings_counter1: service?.offerings_counter1 ?? '',
                offerings_counter2: service?.offerings_counter2 ?? '',
                signatures: ['', ''],
            }
        },
        /**
         * Select a service and load its existing offering values.
         *
         * @param {Object} service Service data.
         * @returns {void}
         */
        selectService(service) {
            this.selectedServiceId = service.id
            this.form = this.createForm(service)
            this.denominationsExpanded = false
        },
        /**
         * Return to the service selection view.
         *
         * @returns {void}
         */
        backToServices() {
            this.selectedServiceId = null
            this.form = this.createForm()
            this.denominationsExpanded = false
        },
        /**
         * Dismiss the save confirmation and return to service selection.
         *
         * @returns {void}
         */
        continueEntry() {
            this.confirmationDismissed = true
            this.backToServices()
        },
        /**
         * Format cents as a German Euro amount.
         *
         * @param {number} cents Amount in cents.
         * @returns {string} Formatted amount.
         */
        formatAmount(cents) {
            return (cents / 100).toLocaleString('de-DE', { minimumFractionDigits: 2 })
        },
        /**
         * Toggle the denomination editor and synchronize its total.
         *
         * @returns {void}
         */
        toggleDenominations() {
            this.denominationsExpanded = !this.denominationsExpanded
            if (this.denominationsExpanded) {
                this.form.offering_amount =
                    this.calculatedTotalCents > 0
                        ? this.formatAmount(this.calculatedTotalCents)
                        : ''
            }
        },
        /**
         * Submit the selected offering through the signed endpoint.
         *
         * @returns {void}
         */
        saveOffering() {
            const denominations = Object.fromEntries(
                Object.entries(this.form.offering_denominations)
                    .filter(([, count]) => Number(count) > 0)
                    .map(([cents, count]) => [cents, Number(count)]),
            )
            this.$inertia.post(this.signedSaveUrl(), {
                service_id: this.selectedServiceId,
                offering_amount:
                    this.calculatedTotalCents > 0
                        ? this.formatAmount(this.calculatedTotalCents)
                        : this.form.offering_amount,
                offering_denominations: denominations,
                offerings_counter1: this.form.offerings_counter1,
                offerings_counter2: this.form.offerings_counter2,
                signatures: this.signatureEntries.map(({ index, name }) => ({
                    name,
                    image: this.form.signatures[index],
                })),
            })
        },
        /**
         * Preserve the signature query parameters from the public page.
         *
         * @returns {string} Signed save URL.
         */
        signedSaveUrl() {
            const saveUrl = new window.URL(
                window.route('offerings.save', { city: this.city.id }),
                window.location.origin,
            )
            const currentUrl = new window.URL(window.location.href)

            ;['expires', 'signature'].forEach((parameter) => {
                const value = currentUrl.searchParams.get(parameter)
                if (value !== null) {
                    saveUrl.searchParams.set(parameter, value)
                }
            })

            return `${saveUrl.pathname}${saveUrl.search}`
        },
    },
}
</script>

<style scoped>
.offerings-page {
    max-width: 720px;
}

.service-selector {
    display: grid;
    gap: 0.2rem;
    width: 100%;
    color: inherit;
    text-decoration: none;
}

.service-time {
    font-size: 1.15rem;
    font-weight: 700;
}

.service-date {
    color: var(--bs-secondary-color);
}

.service-title {
    font-size: 1.1rem;
}

.service-location {
    color: var(--bs-secondary-color);
}

.offering-form {
    position: sticky;
    bottom: 0.75rem;
    box-shadow: 0 0.25rem 1rem rgb(0 0 0 / 12%);
}

.denomination-editor {
    max-height: 50vh;
    overflow-y: auto;
}
</style>

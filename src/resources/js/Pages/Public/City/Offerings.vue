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
                v-for="service in availableServices"
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

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg w-100"
                        data-testid="save-offering"
                    >
                        Opfer speichern
                    </button>
                </div>
            </form>
        </section>
    </main>
</template>

<script>
/**
 * Mobile public page for recording offerings through a signed city link.
 */
export default {
    name: 'PublicCityOfferings',
    props: {
        city: { type: Object, required: true },
        services: { type: Array, default: () => [] },
        fallback: { type: Boolean, default: false },
        denominations: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            denominationsExpanded: false,
            confirmationDismissed: false,
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
            return this.services.find((service) => service.id === this.selectedServiceId)
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
        calculatedTotalCents(total) {
            if (this.denominationsExpanded) {
                this.form.offering_amount = total > 0 ? this.formatAmount(total) : ''
            }
        },
    },
    methods: {
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
            })
        },
        /**
         * Preserve the signature query parameters from the public page.
         *
         * @returns {string} Signed save URL.
         */
        signedSaveUrl() {
            const saveUrl = new URL(
                route('offerings.save', { city: this.city.id }),
                window.location.origin,
            )
            const currentUrl = new URL(window.location.href)

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

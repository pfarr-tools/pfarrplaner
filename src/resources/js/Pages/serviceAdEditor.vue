<template>
    <div class="service-ad-editor">
        <admin-layout
            title="Werbung bearbeiten"
            :no-content-header="true"
        >
            <template #navbar-left>
                <div class="btn-group me-1">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click.prevent="saveAdvertising"
                    >
                        <span class="mdi mdi-content-save d-md-none"></span>
                        <span class="d-none d-md-inline">Speichern</span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <span class="visually-hidden">Weitere Optionen aufklappen</span>
                    </button>
                    <div class="dropdown-menu">
                        <button
                            type="button"
                            class="dropdown-item"
                            @click.prevent="saveAdvertising"
                        >
                            <span class="mdi mdi-content-save"></span> Speichern, ohne zu schließen
                        </button>
                        <button
                            type="button"
                            class="dropdown-item"
                            @click.prevent="cancelEdit"
                        >
                            <span class="mdi mdi-cancel"></span> Schließen, ohne zu speichern
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-danger"
                    @click.prevent="deleteService"
                >
                    <span class="mdi mdi-delete d-md-none"></span
                    ><span class="d-none d-md-inline">Löschen</span>
                </button>
            </template>
            <template #navbar-right>
                <div
                    class="btn-group"
                    role="group"
                    aria-label="Bereich umschalten"
                >
                    <inertia-link
                        class="btn btn-outline-secondary"
                        :href="route('service.edit', service.slug)"
                    >
                        <span class="mdi mdi-pencil me-1"></span
                        ><span class="d-none d-xl-inline">Bearbeiten</span>
                    </inertia-link>
                    <inertia-link
                        class="btn btn-outline-secondary"
                        :href="route('liturgy.editor', service.slug)"
                    >
                        <span class="mdi mdi-view-list me-1"></span
                        ><span class="d-none d-xl-inline">Liturgie</span>
                    </inertia-link>
                    <inertia-link
                        class="btn btn-outline-secondary"
                        :href="route('service.sermon.editor', service.slug)"
                    >
                        <span class="mdi mdi-microphone me-1"></span
                        ><span class="d-none d-xl-inline">Predigt</span>
                    </inertia-link>
                    <button
                        type="button"
                        class="btn btn-secondary"
                    >
                        <span class="mdi mdi-bullhorn me-1"></span
                        ><span class="d-none d-xl-inline">Werbung</span>
                    </button>
                </div>
            </template>
            <div class="service-ad-editor-content">
                <div
                    class="d-flex flex-wrap justify-content-between align-items-baseline gap-3 mb-4"
                >
                    <div>
                        <h1 class="mb-1">Werbung bearbeiten</h1>
                        <p class="text-muted mb-0">
                            {{ service.titleText || service.descriptionText || 'Veranstaltung' }}
                        </p>
                    </div>
                    <span class="badge text-bg-light">{{ service.dateText || '' }}</span>
                </div>
                <ads-tab
                    :service="service"
                    :config="adsConfig"
                    :ad-channels="adChannels"
                />
            </div>
        </admin-layout>
    </div>
</template>

<script>
import { Link as InertiaLink } from '@inertiajs/vue3'
import AdsTab from '../components/ServiceEditor/tabs/AdsTab.vue'

export default {
    name: 'ServiceAdEditor',
    components: { AdsTab, InertiaLink },
    props: {
        service: { type: Object, required: true },
        backRoute: { type: String, default: null },
        adsConfig: { type: Object, required: true },
        adChannels: { type: Object, required: true },
    },
    methods: {
        /** Persist the advertising text and channel schedules. */
        saveAdvertising() {
            this.$inertia.patch(
                route('service.update', this.service.slug),
                {
                    ad_text: this.service.ad_text,
                    ad_configs: this.service.ad_configs,
                },
                { preserveState: true },
            )
        },
        /** Leave the editor without persisting local changes. */
        cancelEdit() {
            if (
                confirm(
                    'Willst du dieses Formular wirklich schließen, ohne zu speichern? Alle deine Änderungen gehen dann verloren!',
                )
            ) {
                window.location.href = this.backRoute || route('service.edit', this.service.slug)
            }
        },
        /** Move the service to the recycle bin after confirmation. */
        deleteService() {
            if (confirm('Willst du diese Veranstaltung wirklich in den Papierkorb verschieben?')) {
                this.$inertia.delete(route('service.destroy', this.service.slug))
            }
        },
    },
}
</script>

<style scoped>
.service-ad-editor-content {
    padding: 0.5rem 1rem 3rem;
}
</style>

<template>
    <div
        v-if="draftAvailable"
        class="alert alert-warning d-flex justify-content-between align-items-center"
    >
        <span>Es gibt einen noch nicht synchronisierten Entwurf auf diesem Gerät.</span>
        <span>
            <button
                class="btn btn-sm btn-primary me-2"
                @click="$emit('restore')"
            >
                Wiederherstellen
            </button>
            <button
                class="btn btn-sm btn-outline-secondary"
                @click="$emit('discard')"
            >
                Verwerfen
            </button>
        </span>
    </div>
    <small
        v-else-if="status"
        class="text-muted"
        role="status"
        >{{ status }}</small
    >
</template>

<script>
export default {
    name: 'AutoSaveNotice',
    props: {
        draftAvailable: { type: Boolean, default: false },
        status: { type: String, default: '' },
    },
    emits: ['restore', 'discard'],
}
</script>

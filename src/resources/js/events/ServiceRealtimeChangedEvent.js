/**
 * @param {Object} payload Broadcast payload from Soketi.
 */
export default class ServiceRealtimeChangedEvent {
    /** Stable event key shared between Vite chunks. */
    static eventName = 'service-realtime-changed'

    /**
     * Normalize a Soketi service-change payload for the EventBus.
     *
     * @param {Object} payload Broadcast payload from Soketi.
     */
    constructor(payload) {
        Object.assign(this, payload)
    }
}

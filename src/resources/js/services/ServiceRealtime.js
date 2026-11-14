import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

/** Browser event used to deliver realtime updates across Vite chunks. */
export const SERVICE_REALTIME_CHANGED_EVENT = 'pfarrplaner:service-changed'

/**
 * Create the browser-side Echo client for the configured Soketi server.
 *
 * @returns {Echo|null} Echo client or null when realtime is not configured.
 */
export function createServiceRealtime() {
    const config = window.Laravel?.realtime
    if (!config?.key || !config?.host || !config?.port) return null

    window.Pusher = Pusher

    const echo = new Echo({
        broadcaster: 'pusher',
        key: config.key,
        cluster: config.cluster || 'mt1',
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: config.scheme === 'https',
        enabledTransports: config.scheme === 'https' ? ['wss'] : ['ws'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        },
    })

    echo.connector?.pusher?.bind_global?.((event, data) => {
        if (event === 'pusher:subscription_error') {
            console.error('[Pfarrplaner] Realtime subscription failed', data)
        }
    })

    echo.connector?.pusher?.connection?.bind?.('error', (error) => {
        console.error('[Pfarrplaner] Realtime connection failed', error)
    })

    return echo
}

/**
 * Start subscriptions for all cities visible to the current user.
 *
 * @param {Echo|null} echo Echo client.
 * @param {number[]} cityIds Visible city identifiers.
 * @returns {Function} Cleanup function.
 */
export function subscribeToServiceChanges(echo, cityIds) {
    if (!echo) return () => {}

    const channelNames = cityIds.map((cityId) => `services.city.${cityId}`)
    channelNames.forEach((channelName) =>
        echo.private(channelName).listen('.service.changed', (payload) => {
            window.dispatchEvent(
                new CustomEvent(SERVICE_REALTIME_CHANGED_EVENT, { detail: payload }),
            )
        }),
    )

    return () => channelNames.forEach((channelName) => echo.leave(channelName))
}

/**
 * Initialize the application-wide service realtime connection.
 *
 * @param {Object} pageProps Initial Inertia page props.
 * @returns {Function} Cleanup function.
 */
export function initializeServiceRealtime(pageProps) {
    const user = pageProps?.currentUser?.data
    const echo = createServiceRealtime()
    const cleanup = subscribeToServiceChanges(echo, (user?.cities || []).map(Number))

    return cleanup
}

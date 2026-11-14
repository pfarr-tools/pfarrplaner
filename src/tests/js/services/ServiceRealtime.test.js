import { afterEach, describe, expect, it } from 'vitest'
import { createServiceRealtime } from '@/services/ServiceRealtime.js'

describe('service realtime connection', () => {
    afterEach(() => {
        delete window.Laravel
    })

    it('passes a cluster to Pusher even for the local Soketi connection', () => {
        window.Laravel = {
            realtime: {
                key: 'local-key',
                host: '127.0.0.1',
                port: 6601,
                scheme: 'http',
            },
        }

        const echo = createServiceRealtime()

        expect(echo.connector.options.cluster).toBe('mt1')
        echo.disconnect()
    })
})

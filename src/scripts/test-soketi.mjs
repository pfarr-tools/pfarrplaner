import crypto from 'node:crypto'
import http from 'node:http'
import Pusher from 'pusher-js'

/* global Buffer, clearTimeout, setTimeout */

/** @type {string} */
const key = process.env.PUSHER_APP_KEY
/** @type {string} */
const secret = process.env.PUSHER_APP_SECRET
/** @type {string} */
const appId = process.env.PUSHER_APP_ID || 'pfarrplaner'
const host = process.env.SOKETI_TEST_HOST || 'soketi'
const port = Number(process.env.SOKETI_TEST_PORT || 6001)
const channel = `soketi-smoke-${Date.now()}`
const event = 'smoke.test'
const payload = { ok: true, source: 'pfarrplaner' }

if (!key || !secret) {
    throw new Error('PUSHER_APP_KEY und PUSHER_APP_SECRET müssen gesetzt sein.')
}

/**
 * Publish one signed Pusher event through Soketi's HTTP API.
 *
 * @param {string} body JSON request body.
 * @returns {Promise<number>} HTTP status code.
 */
function publish(body) {
    return new Promise((resolve, reject) => {
        const params = {
            auth_key: key,
            auth_timestamp: String(Math.floor(Date.now() / 1000)),
            auth_version: '1.0',
            body_md5: crypto.createHash('md5').update(body).digest('hex'),
        }
        const encode = encodeURIComponent
        const query = Object.keys(params)
            .sort()
            .map((name) => `${encode(name)}=${encode(params[name])}`)
            .join('&')
        const path = `/apps/${appId}/events`
        const signature = crypto
            .createHmac('sha256', secret)
            .update(`POST\n${path}\n${query}`)
            .digest('hex')
        const request = http.request(
            {
                host,
                port,
                path: `${path}?${query}&auth_signature=${signature}`,
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Content-Length': Buffer.byteLength(body),
                },
            },
            (response) => {
                response.resume()
                response.on('end', () => resolve(response.statusCode || 0))
            },
        )
        request.on('error', reject)
        request.end(body)
    })
}

const client = new Pusher(key, {
    cluster: process.env.PUSHER_APP_CLUSTER || 'mt1',
    wsHost: host,
    wsPort: port,
    forceTLS: false,
    enabledTransports: ['ws'],
})
const subscription = client.subscribe(channel)

const timeout = setTimeout(() => {
    client.disconnect()
    throw new Error('Soketi-WebSocket-Roundtrip ist abgelaufen.')
}, 10000)

subscription.bind(event, (received) => {
    clearTimeout(timeout)
    client.disconnect()
    if (received.ok !== true || received.source !== payload.source) {
        throw new Error('Soketi-Payload stimmt nicht mit der gesendeten Nachricht überein.')
    }
    console.log('Soketi-WebSocket-Roundtrip: OK')
})

client.connection.bind('connected', async () => {
    const body = JSON.stringify({ name: event, channels: [channel], data: JSON.stringify(payload) })
    const status = await publish(body)
    if (status !== 200) {
        clearTimeout(timeout)
        client.disconnect()
        throw new Error(`Soketi-Publish fehlgeschlagen (HTTP ${status}).`)
    }
})

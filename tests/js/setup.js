import { config } from '@vue/test-utils'
import { vi, beforeEach } from 'vitest'
import EventBus from '@/plugins/EventBus.js'
import LaravelPermission from '@/plugins/LaravelPermission.js'

// window.Laravel — read by AssetMixin, PermissionsMixin, LaravelPermission plugin
window.Laravel = { assetUrl: '/', permissions: [] }

// window.route — Ziggy helper; returns a deterministic fake URL
window.route = vi.fn((name) => `/${name}`)

// window.axios / window.api — minimal mock so $api() doesn't throw
const makeAxiosMock = () => ({
    get: vi.fn(() => Promise.resolve({ data: {} })),
    post: vi.fn(() => Promise.resolve({ data: {} })),
    put: vi.fn(() => Promise.resolve({ data: {} })),
    patch: vi.fn(() => Promise.resolve({ data: {} })),
    delete: vi.fn(() => Promise.resolve({ data: {} })),
    defaults: { headers: { common: {} } },
    create: vi.fn(() => makeAxiosMock()),
})
window.axios = makeAxiosMock()
window.api = makeAxiosMock()

import dayjs from 'dayjs'
import customParseFormat from 'dayjs/plugin/customParseFormat'
dayjs.extend(customParseFormat)
dayjs.isMoment = dayjs.isDayjs
window.moment = dayjs

window.currentUser = {
    data: { id: 1, name: 'Test User', api_token: 'test-token', isPastor: false },
}

// Vue Test Utils global config
config.global.mocks = {
    $page: {
        props: {
            errors: {},
            currentUser: { data: { id: 1, name: 'Test User', api_token: 'test-token' } },
            settings: {},
            permissions: [],
        },
    },
    route: window.route,
    moment: window.moment,
    $inertia: {
        get: vi.fn(), post: vi.fn(), put: vi.fn(),
        patch: vi.fn(), delete: vi.fn(), visit: vi.fn(), reload: vi.fn(),
    },
    $bus: { emit: vi.fn(), on: vi.fn(), off: vi.fn() },
    $can: vi.fn(() => false),
    hasPermission: vi.fn(() => false),
}

config.global.stubs = {
    Link: { template: '<a><slot /></a>' },
    'inertia-link': { template: '<a><slot /></a>' },
    'admin-layout': { template: '<div><slot /></div>' },
    'calendar-nav-top': true,
    'calendar-nav-control-sidebar': true,
    'calendar-day-header': true,
    'calendar-cell': true,
    'calendar-service': true,
    'date-picker': true,
}

config.global.plugins = [EventBus, LaravelPermission]

beforeEach(() => {
    vi.clearAllMocks()
    window.Laravel.permissions = []
})

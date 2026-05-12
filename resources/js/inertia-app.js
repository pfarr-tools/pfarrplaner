/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// CSS
import '../css/prebuild.css'
import '@vuepic/vue-datepicker/dist/main.css'
import '@mdi/font/css/materialdesignicons.min.css'

// Libraries
import $ from 'jquery'
import * as Popper from '@popperjs/core'
import 'bootstrap/dist/js/bootstrap.bundle.min'
import dayjs from 'dayjs'
import 'dayjs/locale/de'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import localizedFormat from 'dayjs/plugin/localizedFormat'
import relativeTime from 'dayjs/plugin/relativeTime'
import isoWeek from 'dayjs/plugin/isoWeek'
import utc from 'dayjs/plugin/utc'
import axios from 'axios'

window.$ = window.jQuery = $
window.Popper = Popper

dayjs.extend(customParseFormat)
dayjs.extend(localizedFormat)
dayjs.extend(relativeTime)
dayjs.extend(isoWeek)
dayjs.extend(utc)
dayjs.locale('de')
dayjs.isMoment = dayjs.isDayjs  // backward-compat shim for legacy moment.isMoment() calls
window.moment = dayjs
window.dayjs = dayjs

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.withCredentials = true
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN'
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN'
window.api = window.axios.create({
    withCredentials: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
})

const currentToken = document.head.querySelector('meta[name="csrf-token"]')
if (currentToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = currentToken.content
    window.api.defaults.headers.common['X-CSRF-TOKEN'] = currentToken.content
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token')
}

// Warm up cookies early (don't block app boot if it fails)
;(async () => { try { await window.axios.get('/csrf-cookie') } catch (e) {} })()

// Inertia / Vue 3
import { createApp, h, reactive } from 'vue'
import { createInertiaApp, Link, router } from '@inertiajs/vue3'

// Plugins
import LaravelPermission from './plugins/LaravelPermission.js'
import EventBus from './plugins/EventBus.js'

// Globally registered components
import AdminLayout from './Pages/Layouts/AdminLayout.vue'
import DatePickerShim from './components/Ui/forms/DatePickerShim.vue'

// Mixins
import AssetMixin from './mixins/Asset.js'
import PfarrplanerAPIMixin from './mixins/PfarrplanerAPI.js'

const pages = import.meta.glob('./Pages/**/*.vue')

createInertiaApp({
    resolve: name => pages[`./Pages/${name}.vue`](),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        const settings = reactive(props.initialPage.props.settings ?? {})
        app.config.globalProperties.$settings = settings

        app.use(plugin)
        app.use(LaravelPermission)
        app.use(EventBus)

        app.config.globalProperties.route = window.route
        app.config.globalProperties.moment = dayjs

        app.mixin({ methods: { route: window.route, moment: dayjs } })
        app.mixin(AssetMixin)
        app.mixin(PfarrplanerAPIMixin)

        app.component('admin-layout', AdminLayout)
        app.component('inertia-link', Link)   // backward-compat alias
        app.component('Link', Link)
        app.component('date-picker', DatePickerShim)

        app.directive('focus', {
            mounted(el) { el.focus(); el.select() },
        })
        app.directive('scrollTo', {
            mounted(el) { el.scrollIntoView() },
        })
        app.directive('bindCustomEvent', {
            mounted(el, binding) {
                const name = binding.arg + '.' + Object.keys(binding.modifiers).join('.')
                el._cevHandler = binding.value
                el._cevName = name
                document.addEventListener(name, binding.value)
            },
            beforeUnmount(el) {
                document.removeEventListener(el._cevName, el._cevHandler)
            },
        })

        app.mount(el)
        window.vm = app
    },
    progress: { color: '#29d', delay: 100, showSpinner: true },
})

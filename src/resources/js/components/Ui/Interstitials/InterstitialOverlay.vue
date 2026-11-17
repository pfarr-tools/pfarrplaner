<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  - @version git: $Id$
  -->

<template>
    <transition name="interstitial-fade">
        <div
            v-if="currentInterstitial"
            class="interstitial-backdrop"
        >
            <div class="interstitial-panel shadow-lg">
                <div
                    class="interstitial-badge"
                    :class="badgeClass"
                >
                    {{ badgeLabel }}
                </div>

                <h2 class="interstitial-title">{{ currentInterstitial.title }}</h2>
                <p
                    v-if="currentInterstitial.text"
                    class="interstitial-text"
                >
                    {{ currentInterstitial.text }}
                </p>

                <ul
                    v-if="(currentInterstitial.details || []).length"
                    class="interstitial-details"
                >
                    <li
                        v-for="detail in currentInterstitial.details"
                        :key="detail"
                    >
                        {{ detail }}
                    </li>
                </ul>

                <ul
                    v-if="(currentInterstitial.links || []).length"
                    class="interstitial-links"
                    aria-label="Weitere Informationen"
                >
                    <li
                        v-for="link in currentInterstitial.links"
                        :key="link.url"
                    >
                        <a
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ link.label }}
                        </a>
                    </li>
                </ul>

                <a
                    v-if="currentInterstitial.action_url"
                    class="btn btn-primary mb-3"
                    :href="currentInterstitial.action_url"
                    data-testid="interstitial-primary-action"
                    :aria-disabled="busy"
                    @click.prevent="handlePrimaryAction"
                >
                    {{ currentInterstitial.action_label || 'Weiter' }}
                </a>

                <div class="interstitial-actions">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :disabled="busy"
                        @click="handleAction('later')"
                    >
                        Später
                    </button>
                    <button
                        v-if="currentInterstitial.dismissible !== false"
                        type="button"
                        class="btn btn-primary"
                        :disabled="busy"
                        @click="handleAction('dismiss')"
                    >
                        Nicht wieder anzeigen
                    </button>
                </div>
                <p
                    v-if="actionError"
                    class="text-danger mt-3 mb-0"
                    role="alert"
                >
                    {{ actionError }}
                </p>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'InterstitialOverlay',
    data() {
        return {
            busy: false,
            actionError: '',
            dismissedKeys: [],
            deferredKeys: [],
            deferredUntil: {},
            currentTime: Date.now(),
            clockInterval: null,
        }
    },
    computed: {
        storageKey() {
            const sessionId = this.$page.props.sessionId || 'guest'
            return `pfarrplaner_interstitials_later:${sessionId}`
        },
        interstitials() {
            return this.$page.props.interstitials || []
        },
        currentInterstitial() {
            return (
                this.interstitials.find((item) => {
                    return (
                        !this.dismissedKeys.includes(item.key) &&
                        !this.deferredKeys.includes(item.key) &&
                        !(
                            this.deferredUntil[item.key] &&
                            Date.parse(this.deferredUntil[item.key]) > this.currentTime
                        )
                    )
                }) || null
            )
        },
        badgeClass() {
            return `interstitial-badge-${this.currentInterstitial?.level || 'info'}`
        },
        badgeLabel() {
            const labels = {
                info: 'Hinweis',
                warning: 'Wichtige Änderung',
                danger: 'Bitte beachten',
            }

            return labels[this.currentInterstitial?.level] || labels.info
        },
    },
    created() {
        this.deferredKeys = this.loadDeferredKeys()
    },
    mounted() {
        this.clockInterval = window.setInterval(this.refreshCurrentTime, 60_000)
    },
    beforeUnmount() {
        if (this.clockInterval) window.clearInterval(this.clockInterval)
    },
    methods: {
        /** Refresh the local clock so an expired snooze can show without a reload. */
        refreshCurrentTime() {
            this.currentTime = Date.now()
        },
        /** Snooze the reminder before visiting the recommended security settings. */
        async handlePrimaryAction() {
            const item = this.currentInterstitial
            if (!item?.action_url || this.busy) return

            if (item.repeatable && !(await this.handleAction('later'))) return

            this.$inertia.get(item.action_url)
        },
        /** Load tab-scoped snoozes used by non-repeatable interstitials. */
        loadDeferredKeys() {
            try {
                return JSON.parse(window.sessionStorage.getItem(this.storageKey) || '[]')
            } catch {
                return []
            }
        },
        saveDeferredKeys() {
            window.sessionStorage.setItem(this.storageKey, JSON.stringify(this.deferredKeys))
        },
        /** Persist a response action and hide the active interstitial locally. */
        async handleAction(action) {
            if (!this.currentInterstitial || this.busy) {
                return
            }

            this.busy = true
            this.actionError = ''
            const key = this.currentInterstitial.key

            try {
                const response = await this.$api().post(
                    window.route('interstitial.update', { key }),
                    { action },
                )

                if (action === 'later') {
                    if (response.data?.snoozed_until) {
                        this.deferredUntil[key] = response.data.snoozed_until
                    } else {
                        if (!this.deferredKeys.includes(key)) {
                            this.deferredKeys.push(key)
                            this.saveDeferredKeys()
                        }
                    }
                } else {
                    this.dismissedKeys.push(key)
                    this.$settings.interstitials = {
                        ...(this.$settings.interstitials || {}),
                        [key]: { status: action },
                    }
                }
                return true
            } catch {
                this.actionError =
                    'Der Hinweis konnte nicht gespeichert werden. Bitte versuchen Sie es erneut.'

                return false
            } finally {
                this.busy = false
            }
        },
    },
}
</script>

<style scoped>
.interstitial-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: rgba(33, 37, 41, 0.52);
    backdrop-filter: blur(2px);
}

.interstitial-panel {
    width: min(42rem, 100%);
    max-height: calc(100vh - 3rem);
    overflow-y: auto;
    padding: 2rem;
    background: #fffdf8;
    border: 1px solid rgba(121, 85, 6, 0.18);
}

.interstitial-links {
    margin-top: -0.5rem;
    margin-bottom: 1rem;
}

.interstitial-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.6rem;
    margin-bottom: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.interstitial-badge-info {
    background: #e7f1ff;
    color: #0a58ca;
}

.interstitial-badge-warning {
    background: #fff3cd;
    color: #997404;
}

.interstitial-badge-danger {
    background: #f8d7da;
    color: #842029;
}

.interstitial-title {
    margin-bottom: 1rem;
    color: #212529;
}

.interstitial-text {
    margin-bottom: 1rem;
    color: #495057;
    white-space: pre-line;
}

.interstitial-details {
    margin: 0 0 1.5rem 1.25rem;
    color: #495057;
}

.interstitial-details li + li {
    margin-top: 0.5rem;
}

.interstitial-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.interstitial-fade-enter-active,
.interstitial-fade-leave-active {
    transition: opacity 0.2s ease;
}

.interstitial-fade-enter-from,
.interstitial-fade-leave-to {
    opacity: 0;
}

@media (max-width: 575.98px) {
    .interstitial-panel {
        padding: 1.25rem;
    }

    .interstitial-actions > * {
        flex: 1 1 auto;
    }
}
</style>

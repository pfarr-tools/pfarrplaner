<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -->

<template>
    <transition name="interstitial-fade">
        <div v-if="currentInterstitial" class="interstitial-backdrop">
            <div class="interstitial-panel shadow-lg">
                <div class="interstitial-badge" :class="badgeClass">
                    {{ badgeLabel }}
                </div>

                <h2 class="interstitial-title">{{ currentInterstitial.title }}</h2>
                <p v-if="currentInterstitial.text" class="interstitial-text">
                    {{ currentInterstitial.text }}
                </p>

                <ul v-if="(currentInterstitial.details || []).length" class="interstitial-details">
                    <li v-for="detail in currentInterstitial.details" :key="detail">{{ detail }}</li>
                </ul>

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
                        type="button"
                        class="btn btn-primary"
                        :disabled="busy"
                        @click="handleAction('dismiss')"
                    >
                        Nicht wieder anzeigen
                    </button>
                </div>
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
            dismissedKeys: [],
            deferredKeys: [],
        };
    },
    computed: {
        interstitials() {
            return this.$page.props.interstitials || [];
        },
        currentInterstitial() {
            return this.interstitials.find((item) => {
                return !this.dismissedKeys.includes(item.key) && !this.deferredKeys.includes(item.key);
            }) || null;
        },
        badgeClass() {
            return `interstitial-badge-${this.currentInterstitial?.level || 'info'}`;
        },
        badgeLabel() {
            const labels = {
                info: 'Hinweis',
                warning: 'Wichtige Änderung',
                danger: 'Bitte beachten',
            };

            return labels[this.currentInterstitial?.level] || labels.info;
        },
    },
    created() {
        this.deferredKeys = this.loadDeferredKeys();
    },
    methods: {
        loadDeferredKeys() {
            try {
                return JSON.parse(sessionStorage.getItem('pfarrplaner_interstitials_later') || '[]');
            } catch (e) {
                return [];
            }
        },
        saveDeferredKeys() {
            sessionStorage.setItem('pfarrplaner_interstitials_later', JSON.stringify(this.deferredKeys));
        },
        async handleAction(action) {
            if (!this.currentInterstitial || this.busy) {
                return;
            }

            this.busy = true;
            const key = this.currentInterstitial.key;

            try {
                await this.$api().post(route('interstitial.update', { key }), { action });

                if (action === 'later') {
                    if (!this.deferredKeys.includes(key)) {
                        this.deferredKeys.push(key);
                        this.saveDeferredKeys();
                    }
                } else {
                    this.dismissedKeys.push(key);
                    this.$settings.interstitials = {
                        ...(this.$settings.interstitials || {}),
                        [key]: { status: action },
                    };
                }
            } finally {
                this.busy = false;
            }
        },
    },
};
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
    background: rgba(33, 37, 41, .52);
    backdrop-filter: blur(2px);
}

.interstitial-panel {
    width: min(42rem, 100%);
    padding: 2rem;
    background: #fffdf8;
    border: 1px solid rgba(121, 85, 6, .18);
}

.interstitial-badge {
    display: inline-flex;
    align-items: center;
    padding: .35rem .6rem;
    margin-bottom: 1rem;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
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
    margin-top: .5rem;
}

.interstitial-actions {
    display: flex;
    gap: .75rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.interstitial-fade-enter-active,
.interstitial-fade-leave-active {
    transition: opacity .2s ease;
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

<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://github.com/pfarr-tools/pfarrplaner
  -->

<template>
    <section class="format-index">
        <div class="format-index__toolbar mb-3">
            <div class="format-index__search">
                <label
                    class="visually-hidden"
                    :for="searchId"
                >
                    {{ searchLabel }}
                </label>
                <span
                    class="mdi mdi-magnify format-index__search-icon"
                    aria-hidden="true"
                ></span>
                <input
                    :id="searchId"
                    v-model.trim="searchTerm"
                    type="search"
                    class="form-control"
                    :placeholder="searchPlaceholder"
                    :aria-label="searchLabel"
                />
            </div>
        </div>

        <section
            v-for="group in visibleGroups"
            :key="group.title"
            class="format-index__group mb-3"
            data-format-group
        >
            <div class="format-index__group-header">
                <h2 class="h5 mb-0">{{ group.title }}</h2>
                <span class="format-index__count">{{ group.items.length }}</span>
            </div>
            <ul class="format-list">
                <li
                    v-for="item in group.items"
                    :key="item.key || item.title"
                    data-format-row
                >
                    <a
                        class="format-row"
                        :href="item.href"
                        :aria-label="`${item.title} öffnen`"
                        @click="selectItem($event, item)"
                    >
                        <span
                            class="format-row__icon"
                            :class="item.icon || 'mdi mdi-file-document-outline'"
                            aria-hidden="true"
                        ></span>
                        <span class="format-row__content">
                            <strong class="format-row__title">{{ item.title }}</strong>
                            <span class="format-row__description">{{ item.description }}</span>
                        </span>
                        <span
                            class="mdi mdi-chevron-right format-row__arrow"
                            aria-hidden="true"
                        ></span>
                    </a>
                </li>
            </ul>
        </section>

        <div
            v-if="!visibleGroups.length"
            class="format-index__empty py-4"
            data-format-empty
        >
            <span
                class="mdi mdi-information-outline format-index__empty-icon"
                aria-hidden="true"
            ></span>
            <div>
                <h2 class="h6 mb-1">{{ displayedEmptyTitle }}</h2>
                <p class="text-muted mb-0">{{ displayedEmptyDescription }}</p>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: 'FormatIndex',
    props: {
        groups: {
            type: Array,
            required: true,
        },
        searchPlaceholder: {
            type: String,
            default: 'Suchen ...',
        },
        searchLabel: {
            type: String,
            default: 'Formate durchsuchen',
        },
        emptyTitle: {
            type: String,
            default: 'Keine Formate verfügbar',
        },
        emptyDescription: {
            type: String,
            default: 'Für diese Auswahl stehen derzeit keine Einträge zur Verfügung.',
        },
    },
    emits: ['select'],
    data() {
        return {
            searchTerm: '',
        }
    },
    computed: {
        searchId() {
            return `format-index-search-${this._.uid}`
        },
        visibleGroups() {
            const searchTerm = this.searchTerm.toLocaleLowerCase()

            return this.groups
                .map((group) => ({
                    ...group,
                    items: group.items.filter((item) =>
                        [item.title, item.description]
                            .filter(Boolean)
                            .join(' ')
                            .toLocaleLowerCase()
                            .includes(searchTerm),
                    ),
                }))
                .filter((group) => group.items.length)
        },
        hasSearchTerm() {
            return this.searchTerm.length > 0
        },
        displayedEmptyTitle() {
            return this.hasSearchTerm ? 'Keine passenden Formate gefunden' : this.emptyTitle
        },
        displayedEmptyDescription() {
            return this.hasSearchTerm
                ? 'Versuche einen anderen Suchbegriff.'
                : this.emptyDescription
        },
    },
    methods: {
        /**
         * Handles selection of a card and preserves regular link navigation.
         *
         * @param {MouseEvent} event The card click event.
         * @param {Object} item The selected format item.
         * @returns {void}
         */
        selectItem(event, item) {
            if (item.inertia) {
                event.preventDefault()
                this.$emit('select', item)
            }
        },
    },
}
</script>

<style scoped lang="scss">
@use '../../../sass/theme' as theme;

.format-index__toolbar {
    display: flex;
    justify-content: flex-end;
}

.format-index__search {
    position: relative;
    width: min(100%, 22rem);
}

.format-index__search .form-control {
    padding-left: 2.5rem;
    border-radius: 0.75rem;
}

.format-index__search-icon {
    position: absolute;
    top: 50%;
    left: 0.85rem;
    z-index: 1;
    color: #6c757d;
    transform: translateY(-50%);
}

.format-index__group-header {
    display: flex;
    gap: 0.6rem;
    align-items: center;
    margin-bottom: 0.4rem;
    padding: 0 0.25rem;
}

.format-index__count {
    display: inline-grid;
    min-width: 1.5rem;
    height: 1.5rem;
    padding: 0 0.35rem;
    color: #6c757d;
    font-size: 0.75rem;
    font-weight: 700;
    place-items: center;
    background: #f1f3f5;
    border-radius: 999px;
}

.format-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.45rem 0.75rem;
    padding: 0;
    margin: 0;
    list-style: none;
}

.format-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    min-height: 4rem;
    padding: 0.7rem 0.85rem;
    color: inherit;
    text-decoration: none;
    background: #fff;
    border: 1px solid rgba(40, 55, 71, 0.12);
    border-radius: 0.5rem;
    transition:
        border-color 150ms ease,
        background-color 150ms ease;
}

.format-row:hover,
.format-row:focus-visible {
    color: inherit;
    background: #f8fbff;
    border-color: theme.theme-color('primary');
    outline: 0;
}

.format-row:focus-visible {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.2);
}

.format-row__icon {
    display: grid;
    flex: 0 0 2.15rem;
    width: 2.15rem;
    height: 2.15rem;
    color: theme.theme-color('primary');
    font-size: 1.15rem;
    place-items: center;
    background: rgba(0, 123, 255, 0.1);
    border-radius: 0.4rem;
}

.format-row__content {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.format-row__title {
    font-size: 0.98rem;
}

.format-row__description {
    overflow: hidden;
    color: #6c757d;
    font-size: 0.84rem;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.format-row__arrow {
    color: #6c757d;
}

.format-index__empty {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    padding-right: 1rem;
    padding-left: 1rem;
    border: 1px solid rgba(40, 55, 71, 0.12);
    border-radius: 0.5rem;
}

.format-index__empty-icon {
    color: #6c757d;
    font-size: 1.5rem;
}

@media (max-width: 767.98px) {
    .format-index__search {
        width: 100%;
    }

    .format-list {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 768px) and (max-width: 1199.98px) {
    .format-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

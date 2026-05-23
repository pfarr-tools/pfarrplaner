<!--
  - Pfarrplaner
  -
  - @package Pfarrplaner
  - @author Christoph Fischer <chris@toph.de>
  - @copyright (c) Christoph Fischer, https://christoph-fischer.org
  - @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
  - @link https://codeberg.org/pfarr.tools/pfarrplaner
  - @version git: $Id$
  -
  - Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
  -
  - Pfarrplaner is based on the Laravel framework (https://laravel.com).
  - This file may contain code created by Laravel's scaffolding functions.
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <section class="liturgy-workspace-header">
        <div class="liturgy-workspace-header__copy">
            <h1 class="liturgy-workspace-header__title mb-0">
                {{ pageTitle }}
                <span v-if="service.timeText && !templateMode" class="liturgy-workspace-header__time mx-1">
                    {{ service.timeText }}
                </span>&middot;
                <span v-if="service.locationText && !templateMode" class="liturgy-workspace-header__location">{{ service.locationText }}
                </span>
            </h1>
            <div v-if="credits" class="liturgy-workspace-header__credits" v-html="credits"></div>
            <div v-else-if="liturgicalLabel" class="liturgy-workspace-header__credits">
                {{ liturgicalLabel }}
            </div>
        </div>
    </section>
</template>

<script>
import dayjs from 'dayjs';

export default {
    name: "LiturgyWorkspaceHeader",
    props: {
        service: Object,
        templateMode: Boolean,
        editable: Boolean,
        blockCount: {
            type: Number,
            default: 0,
        },
    },
    computed: {
        pageTitle() {
            if (this.templateMode) return 'Vorlage bearbeiten';
            return 'Liturgie für ' + dayjs(this.service.date).locale('de').format('DD.MM.YYYY');
        },
        liturgicalLabel() {
            return this.service.liturgicalInfo?.Bezeichnung || '';
        },
        credits() {
            let ministries = {
                'P': this.service.pastors,
                'O': this.service.organists,
                'M': this.service.sacristans,
                ...this.service.ministriesByCategory,
            };
            let c = [];
            for (const category in ministries) {
                let names = [];
                ministries[category].forEach(person => names.push(person.name));
                if (names.length) c.push(category + ': ' + names.join(', '));
            }
            return c.join(' &middot; ');
        },
    },
}
</script>

<style scoped>
.liturgy-workspace-header {
    display: flex;
    margin-bottom: 0.35rem;
    padding: 0.1rem 0 0.15rem;
}

.liturgy-workspace-header__title {
    font-size: clamp(1.4rem, 1.1rem + 1vw, 2rem);
    font-weight: 700;
    line-height: 1.1;
}

.liturgy-workspace-header__time {
    font-size: 0.75em;
    font-weight: 500;
    color: var(--bs-secondary-color);
    white-space: nowrap;
}

.liturgy-workspace-header__location {
    font-size: 0.75em;
    font-weight: 500;
    color: var(--bs-secondary-color);
}

.liturgy-workspace-header__credits {
    margin-top: 0.35rem;
    font-size: 0.92rem;
    color: var(--bs-secondary-color);
}

@media (max-width: 767.98px) {
    .liturgy-workspace-header__time {
        display: block;
        margin-left: 0;
        margin-top: 0.2rem;
    }

    .liturgy-workspace-header__location {
        display: block;
        margin-left: 0;
        margin-top: 0.1rem;
    }
}
</style>

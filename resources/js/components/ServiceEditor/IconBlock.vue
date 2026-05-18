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

<script>
export default {
    name: "IconBlock",
    props: ['icon'],
}
</script>

<template>
    <section class="icon-block card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row align-items-start">
                <div class="d-none d-md-block col-md-1 icon-block-symbol">
                <span :class="icon"></span>
            </div>
            <div class="col-12 col-md-11">
                <slot/>
            </div>
        </div>
        </div>
    </section>
</template>

<style scoped>
.icon-block-symbol {
    color: darkgray;
    font-size: 2.5rem;
    line-height: 1;
    text-align: center;
}
</style>

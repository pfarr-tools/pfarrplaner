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

const mix = require('laravel-mix');
const webpack = require('webpack');
const path = require('path');
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');

mix.js('resources/js/inertia-app.js', 'public/js')
    .vue({ version: 2 })
    .autoload({
        jquery: ['$', 'window.jQuery', 'jQuery'],
        vue: ['Vue', 'window.Vue'],
        moment: ['moment', 'window.moment'],
    })
    .sourceMaps()
    .postCss('resources/css/prebuild.css', 'public/css/app.css', [
        require('autoprefixer'),
    ])
    .webpackConfig({
        output: {
            chunkFilename: 'js/[name].js?id=[chunkhash]',
        },
        module: {
            rules: [
                {
                    test: /\.m?js$/,
                    exclude: /node_modules\/(?!admin-lte)/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: ['@babel/preset-env'],
                            plugins: ['@babel/plugin-proposal-class-properties'],
                        },
                    },
                },
            ],
        },
        resolve: {
            alias: {
                // force all jquery imports to one copy
                jquery: path.resolve(__dirname, 'node_modules/jquery/'),

                vue$: 'vue/dist/vue.esm.js',
                '@': path.resolve(__dirname, 'resources/js/components'),
            },
        },
        plugins: [
            new webpack.ProvidePlugin({
                jQuery: 'jquery',
                $: 'jquery',
                jquery: 'jquery',
            }),
            new MomentLocalesPlugin({ localesToKeep: ['de-de'] }),
        ],
    })
    .babelConfig({
        plugins: ['@babel/plugin-syntax-dynamic-import'],
    })
    .version();

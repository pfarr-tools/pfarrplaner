<?php
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

use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Tab;

return [
    'word' => [
        'default' => [
            'layout' => [
                'orientation' => 'portrait',
                'pageSizeH' => 16837.795275591, // 29.7cm
                'pageSizeW' => 11905.511811024, // 21cm
                'marginTop' => 850.3937007874, // 1.5cm
                'marginBottom' => 850.3937007874, // 1.5cm
                'marginLeft' => 850.3937007874, // 1.5cm
                'marginRight' => 850.3937007874, // 1.5cm
            ],
            'styles' => [
                'paragraphs' => [
                    'default' => [
                        'alignment' => 'start',
                        'indentation' => [
                            'left' => 0,
                            'right' => 0,
                            'firstLine' => 0,
                            'hanging' => 0,
                        ],
                        'lineHeight' => 1.08,
                        'spaceBefore' => 0,
                        'spaceAfter' => 160, // 8pt
                    ],
                    'titles' => [
                        1 => [
                            'alignment' => Jc::START,
                            'indentation' => [
                                'left' => 0,
                                'right' => 0,
                                'firstLine' => 0,
                                'hanging' => 0,
                            ],
                            'keepNext' => true,
                            'lineHeight' => 1.08,
                            'spaceBefore' => Converter::pointToTwip(12),
                            'spaceAfter' => 0,
                        ],
                        2 => [
                            'alignment' => 'start',
                            'indentation' => [
                                'left' => 0,
                                'right' => 0,
                                'firstLine' => 0,
                                'hanging' => 0,
                            ],
                            'keepNext' => true,
                            'lineHeight' => 1.08,
                            'spaceBefore' => 40, // 2pt
                            'spaceAfter' => 0,
                        ],
                        3 => [
                            'alignment' => 'start',
                            'indentation' => [
                                'left' => 0,
                                'right' => 0,
                                'firstLine' => 0,
                                'hanging' => 0,
                            ],
                            'keepNext' => true,
                            'lineHeight' => 1.08,
                            'spaceBefore' => 40, // 2pt
                            'spaceAfter' => 0,
                        ],
                    ],
                    'custom' => [
                        'Zitat' => [
                            'alignment' => 'both',
                            'indentation' => [
                                'left' => 4535.4330708661, // 1cm
                                'right' => 4535.4330708661, // 1cm
                                'firstLine' => 0,
                                'hanging' => 0,
                            ],
                            'lineHeight' => 1.08,
                            'spaceBefore' => 200, // 10pt
                            'spaceAfter' => 160, // 8pt
                        ],
                        'Standard mit Anweisungen' => [
                            'alignment' => 'start',
                            'indentation' => [
                                'left' => 720, // 1.27cm
                                'right' => 0,
                                'firstLine' => 0,
                                'hanging' => 720, // 1.27cm
                            ],
                            'lineHeight' => 1.08,
                            'spaceBefore' => 0,
                            'spaceAfter' => 160, // 8pt
                            'tabs' => [
                                ['position' => 720, 'type' => 'left'],
                                //new Tab('left', 720), // 1.27cm
                            ],
                        ]
                    ],
                ],
                'fonts' => [
                    'titles' => [
                        1 => [
                            'name' => 'Sarabun Semibold',
                            'size' => 16,
                            'bold' => false,
                            'italic' => false,
                        ],
                        2 => [
                            'name' => 'Sarabun Semibold',
                            'size' => 13,
                            'bold' => false,
                            'italic' => false,
                        ],
                        3 => [
                            'name' => 'Sarabun Semibold',
                            'size' => 12,
                            'bold' => false,
                            'italic' => false,
                        ],
                    ],
                    'Standard' => [
                        'name' => 'Sarabun Light',
                        'size' => 11,
                        'bold' => false,
                        'italic' => false,
                    ],
                    'custom' => [
                        'Zitat' => [
                            'name' => 'Sarabun Light',
                            'size' => 10,
                            'bold' => false,
                            'italic' => false,
                        ],
                    ]
                ],

            ]
        ]
    ]
];

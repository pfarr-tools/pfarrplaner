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
 */

namespace App\UI\Modules;

use App\Http\Controllers\PapierkorbController;

class PapierkorbModule extends AbstractModule
{
    protected $title = 'Papierkorb';
    protected $icon = 'mdi mdi-delete-restore';
    protected $color = '#b02a37';
    protected $defaultRoute = 'admin.trash.index';

    public function isActive(): bool
    {
        return auth()->check() && PapierkorbController::hasAdminModuleAccess(auth()->user());
    }

    public function addItems(array $items): array
    {
        $items[] = [
            'text' => 'Papierkorb',
            'icon' => 'mdi mdi-delete-restore',
            'url' => route('admin.trash.index'),
            'active' => request()->is('admin/papierkorb*'),
            'inertia' => true,
        ];

        return $items;
    }
}

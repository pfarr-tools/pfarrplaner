<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Feature;

use App\Models\People\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutFeatureTest extends TestCase
{
    public function test_about_page_shares_repository_and_runtime_information(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('About')
                ->where('repositoryUrl', 'https://github.com/pfarr-tools/pfarrplaner')
                ->has('runtime.images')
                ->has('runtime.services')
                ->where('runtime.images.application', 'pfarrtools/pfarrplaner:latest')
            );
    }
}

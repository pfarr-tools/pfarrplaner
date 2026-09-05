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

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class LiturgyEditorPage extends Page
{
    protected string $slug;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    /** @return string */
    public function url(): string
    {
        return route('liturgy.editor', $this->slug);
    }

    /** @param Browser $browser */
    public function assert(Browser $browser): void
    {
        $browser->waitFor('#app', 10)->assertDontSee('500');
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@toolbar'      => '.btn-toolbar',
            '@saveTemplate' => 'button.btn-primary',
        ];
    }
}

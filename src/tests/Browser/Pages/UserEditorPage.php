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

class UserEditorPage extends Page
{
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /** @return string */
    public function url(): string
    {
        return route('user.edit', $this->userId);
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
            '@nameInput'  => '[name="name"]',
            '@emailInput' => '[name="email"]',
            '@saveButton' => 'button[type="submit"], button.btn-primary',
        ];
    }
}

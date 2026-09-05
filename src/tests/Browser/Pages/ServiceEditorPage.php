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

class ServiceEditorPage extends Page
{
    protected string $slug;

    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    /** @return string */
    public function url(): string
    {
        return route('service.edit', $this->slug);
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
            '@saveButton'  => 'button.btn-primary',
            '@form'        => '#serviceEditorForm',
            '@deleteButton' => 'button.btn-danger',
            '@dateInput' => '.home-tab .dp__input',
        ];
    }

    /**
     * Set the main service date/time input via the VueDatePicker text field.
     *
     * @param Browser $browser
     * @param string $value
     * @return void
     */
    public function setDateTime(Browser $browser, string $value): void
    {
        $browser->waitFor($this->elements()['@dateInput'], 10);
        $browser->script(<<<JS
const input = document.querySelector('.home-tab .dp__input');
if (!input) throw new Error('Service date input not found');
input.focus();
input.value = '{$value}';
input.dispatchEvent(new Event('input', { bubbles: true }));
input.dispatchEvent(new Event('change', { bubbles: true }));
input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
input.dispatchEvent(new KeyboardEvent('keyup', { key: 'Enter', bubbles: true }));
input.dispatchEvent(new Event('blur', { bubbles: true }));
JS);
    }
}

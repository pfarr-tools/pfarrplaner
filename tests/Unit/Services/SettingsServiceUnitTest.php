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

namespace Tests\Unit\Services;

use App\Models\People\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService();
        $this->user = User::factory()->create();
    }

    public function testSetAndGetSetting(): void
    {
        $this->service->set($this->user, 'test_key', 'test_value');
        $result = $this->service->get($this->user, 'test_key');
        $this->assertEquals('test_value', $result);
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $result = $this->service->get($this->user, 'nonexistent_key', 'default');
        $this->assertEquals('default', $result);
    }

    public function testHasReturnsTrueAfterSet(): void
    {
        $this->service->set($this->user, 'my_key', 'value');
        $this->assertTrue($this->service->has($this->user, 'my_key'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->service->has($this->user, 'nonexistent'));
    }

    public function testAllReturnsCollection(): void
    {
        $this->service->set($this->user, 'key1', 'val1');
        $this->service->set($this->user, 'key2', 'val2');
        $all = $this->service->all($this->user);
        $this->assertNotEmpty($all);
    }

    public function testSetOverwritesExistingValue(): void
    {
        $this->service->set($this->user, 'key', 'first');
        $this->service->set($this->user, 'key', 'second');
        $this->assertEquals('second', $this->service->get($this->user, 'key'));
    }
}

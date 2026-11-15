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

namespace Tests\Unit\Services;

use App\Models\People\User;
use App\Services\FirstRunInstallerService;
use App\Services\RoleService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FirstRunInstallerServiceUnitTest extends TestCase
{
    public function test_build_environment_file_replaces_configured_values(): void
    {
        $service = app(FirstRunInstallerService::class);
        $template = "APP_NAME=\"Pfarrplaner\"\nAPP_KEY=\nAPP_DEBUG=false\nDB_CONNECTION=mysql\n";

        $result = $service->buildEnvironmentFile($template, [
            'APP_NAME' => 'Meine Instanz',
            'APP_KEY' => 'base64:test',
            'APP_DEBUG' => true,
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => '/tmp/pfarrplaner.sqlite',
        ]);

        $this->assertStringContainsString('APP_NAME="Meine Instanz"', $result);
        $this->assertStringContainsString('APP_KEY=base64:test', $result);
        $this->assertStringContainsString('APP_DEBUG=true', $result);
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $result);
        $this->assertStringContainsString('DB_DATABASE=/tmp/pfarrplaner.sqlite', $result);
    }

    public function test_create_or_update_admin_user_creates_super_admin(): void
    {
        $service = app(FirstRunInstallerService::class);

        $user = $service->createOrUpdateAdminUser([
            'title' => 'Pfarrer',
            'first_name' => 'Chris',
            'last_name' => 'Fischer',
            'email' => 'admin@example.com',
            'password' => 'Geheim123!',
            'must_change_password' => true,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('admin@example.com', $user->email);
        $this->assertSame('Chris', $user->first_name);
        $this->assertSame('Fischer', $user->last_name);
        $this->assertTrue((bool) $user->must_change_password);
        $this->assertTrue($user->hasRole(RoleService::ROLE_SUPER_ADMIN));
        $this->assertNotSame('Geheim123!', $user->getRawOriginal('password'));
    }

    /** Weak administrator passwords are rejected during first-run setup. */
    public function test_create_or_update_admin_user_rejects_weak_password(): void
    {
        $this->expectException(ValidationException::class);

        app(FirstRunInstallerService::class)->createOrUpdateAdminUser([
            'title' => '',
            'first_name' => 'Chris',
            'last_name' => 'Fischer',
            'email' => 'weak@example.com',
            'password' => 'weak1234',
            'must_change_password' => false,
        ]);
    }
}

<?php

namespace Tests\Unit;

use App\Providers\HorizonServiceProvider;
use PHPUnit\Framework\TestCase;

class HorizonAccessTest extends TestCase
{
    public function test_horizon_access_uses_the_configured_email_allowlist(): void
    {
        $allowed = ['admin@example.org', ' office@example.org '];

        $this->assertTrue(HorizonServiceProvider::isEmailAllowed('ADMIN@example.org', $allowed));
        $this->assertTrue(HorizonServiceProvider::isEmailAllowed(' office@example.org ', $allowed));
        $this->assertFalse(HorizonServiceProvider::isEmailAllowed('other@example.org', $allowed));
    }
}

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

namespace Tests\Unit\Configuration;

use App\Integrations\Youtube\YoutubeIntegration;
use Tests\TestCase;

/**
 * Verifies the environment-backed Google configuration used by YouTube.
 */
class GoogleConfigurationUnitTest extends TestCase
{
    /**
     * The configuration exposes the OAuth values without requiring a JSON file.
     */
    public function test_google_configuration_uses_environment_values(): void
    {
        config([
            'google.client_id' => 'client-id',
            'google.client_secret' => 'client-secret',
        ]);

        $this->assertSame('client-id', config('google.client_id'));
        $this->assertSame('client-secret', config('google.client_secret'));
        $this->assertTrue(YoutubeIntegration::isConfigured());
    }

    /**
     * Missing OAuth values disable the integration cleanly.
     */
    public function test_missing_google_configuration_disables_youtube(): void
    {
        config([
            'google.client_id' => null,
            'google.client_secret' => null,
        ]);

        $this->assertFalse(YoutubeIntegration::isConfigured());
    }
}

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

use Illuminate\Support\Env;
use Tests\TestCase;

class LoggingConfigurationUnitTest extends TestCase
{
    public function test_stack_uses_stderr_by_default(): void
    {
        $channels = config('logging.channels.stack.channels');

        $this->assertSame(['stderr'], $channels);
    }

    public function test_stack_includes_sentry_logs_when_enabled(): void
    {
        Env::getRepository()->set('SENTRY_LOGS', 'true');

        try {
            $logging = require base_path('config/logging.php');

            $this->assertSame(
                ['stderr', 'sentry_logs'],
                $logging['channels']['stack']['channels']
            );
        } finally {
            Env::getRepository()->clear('SENTRY_LOGS');
        }
    }

    public function test_stack_includes_daily_logs_when_enabled(): void
    {
        Env::getRepository()->set('DAILY_LOGS', 'true');

        try {
            $logging = require base_path('config/logging.php');

            $this->assertSame(
                ['daily', 'stderr'],
                $logging['channels']['stack']['channels']
            );
        } finally {
            Env::getRepository()->clear('DAILY_LOGS');
        }
    }
}

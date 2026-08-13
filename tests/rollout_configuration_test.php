<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_forcemfa;

use local_forcemfa\local\qualifying_factor_checker;
use local_forcemfa\local\rollout_configuration;

/**
 * Tests for supported tool_mfa rollout configuration detection.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\rollout_configuration
 */
final class rollout_configuration_test extends \advanced_testcase {
    /**
     * Tests a usable first-factor enrollment configuration.
     *
     * @return void
     */
    public function test_usable_configuration(): void {
        $this->resetAfterTest(true);
        set_config('enabled', 1, 'tool_mfa');
        set_config('enabled', 1, 'factor_nosetup');
        set_config('weight', 100, 'factor_nosetup');
        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 100, 'factor_totp');

        $configuration = new rollout_configuration(new qualifying_factor_checker());
        $this->assertTrue($configuration->is_usable());
        $this->assertSame([], $configuration->get_issues());
    }

    /**
     * Tests each required rollout prerequisite.
     *
     * @return void
     */
    public function test_reports_missing_prerequisites(): void {
        $this->resetAfterTest(true);
        set_config('enabled', 0, 'tool_mfa');
        set_config('enabled', 1, 'factor_nosetup');
        set_config('weight', 50, 'factor_nosetup');
        set_config('enabled', 0, 'factor_totp');

        $configuration = new rollout_configuration(new qualifying_factor_checker());
        $this->assertEqualsCanonicalizing([
            rollout_configuration::ISSUE_TOOL_MFA_DISABLED,
            rollout_configuration::ISSUE_NO_QUALIFYING_FACTOR,
            rollout_configuration::ISSUE_NOSETUP_WEIGHT,
        ], $configuration->get_issues());

        set_config('enabled', 0, 'factor_nosetup');
        $this->assertContains(
            rollout_configuration::ISSUE_NOSETUP_DISABLED,
            $configuration->get_issues(),
        );
    }

    /**
     * Tests that a configured-looking but unusable SMS factor is reported.
     *
     * @return void
     */
    public function test_sms_without_gateway_is_not_a_usable_rollout_factor(): void {
        $this->resetAfterTest(true);
        set_config('enabled', 1, 'tool_mfa');
        set_config('enabled', 1, 'factor_nosetup');
        set_config('weight', 100, 'factor_nosetup');
        set_config('enabled', 1, 'factor_sms');
        set_config('weight', 100, 'factor_sms');
        unset_config('smsgateway', 'factor_sms');

        $configuration = new rollout_configuration(new qualifying_factor_checker());
        $this->assertContains(
            rollout_configuration::ISSUE_NO_QUALIFYING_FACTOR,
            $configuration->get_issues(),
        );
        $this->assertFalse($configuration->is_usable());
    }
}

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

use local_forcemfa\local\global_policy_provider;

/**
 * Tests Moodle's post-authentication web-service callback.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_forcemfa_override_webservice_execution
 */
final class webservice_callback_test extends \advanced_testcase {
    /**
     * Tests denial before an external function may execute.
     *
     * @return void
     */
    public function test_authenticated_user_without_factor_is_denied(): void {
        global $CFG;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/local/forcemfa/lib.php');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_config('policy', global_policy_provider::POLICY_EVERYBODY, 'local_forcemfa');
        set_config('enabled', 1, 'tool_mfa');
        set_config('enabled', 1, 'factor_nosetup');
        set_config('weight', 100, 'factor_nosetup');
        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 100, 'factor_totp');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('You must configure a multi-factor authentication method');
        \local_forcemfa_override_webservice_execution(new \stdClass(), []);
    }

    /**
     * Tests normal execution when the policy is disabled.
     *
     * @return void
     */
    public function test_disabled_policy_returns_control_to_moodle(): void {
        global $CFG;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/local/forcemfa/lib.php');

        $this->setUser($this->getDataGenerator()->create_user());
        set_config('policy', global_policy_provider::POLICY_DISABLED, 'local_forcemfa');

        $this->assertFalse(\local_forcemfa_override_webservice_execution(new \stdClass(), []));
    }

    /**
     * Tests fail-safe policy validation on web-service requests.
     *
     * @return void
     */
    public function test_invalid_policy_denies_ordinary_user(): void {
        global $CFG;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/local/forcemfa/lib.php');

        $this->setUser($this->getDataGenerator()->create_user());
        set_config('policy', 'invalid', 'local_forcemfa');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Multi-factor authentication setup is temporarily unavailable');
        \local_forcemfa_override_webservice_execution(new \stdClass(), []);
    }
}

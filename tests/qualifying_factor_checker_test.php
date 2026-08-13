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

/**
 * Tests for the current-user qualifying factor checker.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\qualifying_factor_checker
 */
final class qualifying_factor_checker_test extends \advanced_testcase {
    /**
     * Tests supported core user-setup factors.
     *
     * The checker uses the public factor contract, so compatible third-party factors
     * receive the same treatment without component-specific code.
     *
     * @param string $factorname
     * @dataProvider user_setup_factor_provider
     * @return void
     */
    public function test_active_user_setup_factors_qualify(string $factorname): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        set_config('enabled', 1, 'factor_' . $factorname);
        set_config('weight', 100, 'factor_' . $factorname);
        if ($factorname === 'sms') {
            set_config('smsgateway', 1, 'factor_sms');
        }
        $this->create_user_factor($user, $factorname);

        $checker = new qualifying_factor_checker();
        $this->assertTrue($checker->has_available_factor());
        $this->assertTrue($checker->has_factor($user));
    }

    /**
     * Tests that SMS without a gateway cannot make rollout health pass.
     *
     * An already active SMS instance remains genuine MFA state; availability and
     * current-user qualification are intentionally separate questions.
     *
     * @return void
     */
    public function test_sms_without_gateway_is_not_available(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_config('enabled', 1, 'factor_sms');
        set_config('weight', 100, 'factor_sms');
        unset_config('smsgateway', 'factor_sms');
        $this->create_user_factor($user, 'sms');

        $checker = new qualifying_factor_checker();
        $this->assertFalse($checker->has_available_factor());
        $this->assertFalse($checker->can_setup_factor($user));
        $this->assertTrue($checker->has_factor($user));
    }

    /**
     * Tests current-user enrollment availability.
     *
     * @return void
     */
    public function test_current_user_can_setup_available_factor(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 100, 'factor_totp');

        $checker = new qualifying_factor_checker();
        $this->assertTrue($checker->can_setup_factor($user));
        $this->assertFalse($checker->can_setup_factor($otheruser));
    }

    /**
     * Returns core user-setup factors representative of the supported factor contract.
     *
     * @return array
     */
    public static function user_setup_factor_provider(): array {
        return [
            'TOTP' => ['totp'],
            'WebAuthn' => ['webauthn'],
            'SMS' => ['sms'],
        ];
    }

    /**
     * Tests that revoked and zero-weight factors do not qualify.
     *
     * @return void
     */
    public function test_revoked_and_zero_weight_factors_do_not_qualify(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 100, 'factor_totp');
        $record = $this->create_user_factor($user, 'totp');
        $DB->set_field('tool_mfa', 'revoked', 1, ['id' => $record->id]);

        $checker = new qualifying_factor_checker();
        $this->assertFalse($checker->has_factor($user));

        $DB->set_field('tool_mfa', 'revoked', 0, ['id' => $record->id]);
        set_config('weight', 0, 'factor_totp');
        $this->assertFalse($checker->has_available_factor());
        $this->assertFalse($checker->has_factor($user));
    }

    /**
     * Tests that passive factors and another user's records do not qualify.
     *
     * @return void
     */
    public function test_only_supplied_user_and_genuine_setup_factors_are_inspected(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        set_config('enabled', 1, 'factor_auth');
        set_config('weight', 100, 'factor_auth');
        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 100, 'factor_totp');
        $this->create_user_factor($otheruser, 'totp');

        $checker = new qualifying_factor_checker();
        $this->assertFalse($checker->has_factor($user));
        $this->assertTrue($checker->has_factor($otheruser));
    }

    /**
     * Creates a current-user factor record through tool_mfa's test generator.
     *
     * @param \stdClass $user
     * @param string $factorname
     * @return \stdClass
     */
    private function create_user_factor(\stdClass $user, string $factorname): \stdClass {
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mfa');
        return $generator->create_user_factors([
            'username' => $user->username,
            'factor' => $factorname,
            'label' => 'Test factor',
        ]);
    }
}

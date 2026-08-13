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
 * Tests for the global forced MFA policy.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\global_policy_provider
 */
final class global_policy_provider_test extends \advanced_testcase {
    /**
     * Tests disabled, site-administrator-exempt, and everybody modes.
     *
     * @return void
     */
    public function test_policy_modes_and_site_administrator_identity(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $provider = new global_policy_provider();
        $user = $this->getDataGenerator()->create_user();

        set_config('policy', global_policy_provider::POLICY_DISABLED, 'local_forcemfa');
        $decision = $provider->get_decision($user);
        $this->assertTrue($decision->is_valid());
        $this->assertFalse($decision->is_enforced());

        set_config('policy', global_policy_provider::POLICY_EXCEPT_SITE_ADMINS, 'local_forcemfa');
        $this->assertTrue($provider->get_decision($user)->is_enforced());

        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $manager->id, \context_system::instance()->id);
        $this->assertTrue($provider->get_decision($manager)->is_enforced());

        // Only Moodle's site administrator list creates the exemption. Roles and capabilities do not.
        $CFG->siteadmins .= ',' . $user->id;
        $this->assertFalse($provider->get_decision($user)->is_enforced());

        set_config('policy', global_policy_provider::POLICY_EVERYBODY, 'local_forcemfa');
        $this->assertTrue($provider->get_decision($user)->is_enforced());
    }

    /**
     * Tests that malformed stored policies are distinguishable from disabled.
     *
     * @param string $value
     * @dataProvider invalid_policy_provider
     * @return void
     */
    public function test_invalid_policy_values_fail_validation(string $value): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_config('policy', $value, 'local_forcemfa');

        $provider = new global_policy_provider();
        $this->assertNull($provider->get_configured_policy());
        $decision = $provider->get_decision($user);
        $this->assertFalse($decision->is_valid());
        $this->assertFalse($decision->is_enforced());
    }

    /**
     * Returns malformed policy values.
     *
     * @return array
     */
    public static function invalid_policy_provider(): array {
        return [
            'negative' => ['-1'],
            'out of range' => ['3'],
            'non-numeric' => ['enabled'],
            'decimal' => ['1.0'],
            'empty' => [''],
        ];
    }

    /**
     * Tests the missing-setting default.
     *
     * @return void
     */
    public function test_missing_policy_defaults_to_disabled(): void {
        $this->resetAfterTest();
        unset_config('policy', 'local_forcemfa');

        $provider = new global_policy_provider();
        $this->assertSame(global_policy_provider::POLICY_DISABLED, $provider->get_configured_policy());
    }
}

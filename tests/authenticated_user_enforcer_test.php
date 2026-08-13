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

use local_forcemfa\local\authenticated_user_enforcer;
use local_forcemfa\local\policy_decision;
use local_forcemfa\local\policy_provider_interface;
use local_forcemfa\local\qualifying_factor_checker_interface;
use local_forcemfa\local\rollout_configuration;

/**
 * Tests for shared browser and web-service enforcement decisions.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\authenticated_user_enforcer
 */
final class authenticated_user_enforcer_test extends \advanced_testcase {
    /**
     * Tests the principal enforcement outcomes.
     *
     * @return void
     */
    public function test_enforcement_results(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $policy = $this->createStub(policy_provider_interface::class);
        $checker = $this->createStub(qualifying_factor_checker_interface::class);
        $configuration = $this->createStub(rollout_configuration::class);

        $policy->method('get_decision')->willReturnOnConsecutiveCalls(
            policy_decision::valid(false),
            policy_decision::valid(true),
            policy_decision::valid(true),
        );
        $checker->method('has_factor')->willReturnOnConsecutiveCalls(true, false);
        $checker->method('can_setup_factor')->willReturn(true);
        $configuration->method('is_usable')->willReturn(true);

        $enforcer = $this->create_ready_enforcer($policy, $checker, $configuration);
        $this->assertSame(authenticated_user_enforcer::RESULT_NOT_ENFORCED, $enforcer->evaluate($user));
        $this->assertSame(authenticated_user_enforcer::RESULT_SATISFIED, $enforcer->evaluate($user));
        $this->assertSame(authenticated_user_enforcer::RESULT_SETUP_REQUIRED, $enforcer->evaluate($user));
    }

    /**
     * Tests fail-safe invalid policy handling and site-administrator repair access.
     *
     * @return void
     */
    public function test_invalid_policy_fails_safe_with_site_admin_repair_access(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();
        $CFG->siteadmins .= ',' . $admin->id;

        $policy = $this->createStub(policy_provider_interface::class);
        $policy->method('get_decision')->willReturn(policy_decision::invalid());
        $checker = $this->createMock(qualifying_factor_checker_interface::class);
        $checker->expects($this->never())->method('has_factor');
        $configuration = $this->createStub(rollout_configuration::class);
        $enforcer = $this->create_ready_enforcer($policy, $checker, $configuration);

        $this->assertSame(
            authenticated_user_enforcer::RESULT_CONFIGURATION_UNAVAILABLE,
            $enforcer->evaluate($user),
        );
        $this->assertSame(authenticated_user_enforcer::RESULT_REPAIR_ALLOWED, $enforcer->evaluate($admin));
    }

    /**
     * Tests that a user-specific enrollment dead end fails safely.
     *
     * @return void
     */
    public function test_unenrollable_user_gets_configuration_failure(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $policy = $this->createStub(policy_provider_interface::class);
        $policy->method('get_decision')->willReturn(policy_decision::valid(true));
        $checker = $this->createStub(qualifying_factor_checker_interface::class);
        $checker->method('has_factor')->willReturn(false);
        $checker->method('can_setup_factor')->willReturn(false);
        $configuration = $this->createStub(rollout_configuration::class);
        $configuration->method('is_usable')->willReturn(true);

        $enforcer = $this->create_ready_enforcer($policy, $checker, $configuration);
        $this->assertSame(
            authenticated_user_enforcer::RESULT_CONFIGURATION_UNAVAILABLE,
            $enforcer->evaluate($user),
        );
    }

    /**
     * Tests exception conversion for non-page transports.
     *
     * @return void
     */
    public function test_non_page_results_throw_localized_exceptions(): void {
        $this->resetAfterTest();
        $policy = $this->createStub(policy_provider_interface::class);
        $checker = $this->createStub(qualifying_factor_checker_interface::class);
        $configuration = $this->createStub(rollout_configuration::class);
        $enforcer = $this->create_ready_enforcer($policy, $checker, $configuration);

        try {
            $enforcer->enforce_non_page_result(authenticated_user_enforcer::RESULT_SETUP_REQUIRED);
            $this->fail('Setup-required result did not throw.');
        } catch (\moodle_exception $exception) {
            $this->assertStringContainsString('configure a multi-factor authentication method', $exception->getMessage());
        }

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Multi-factor authentication setup is temporarily unavailable');
        $enforcer->enforce_non_page_result(authenticated_user_enforcer::RESULT_CONFIGURATION_UNAVAILABLE);
    }

    /**
     * Creates an enforcer whose Moodle MFA readiness result is deterministic.
     *
     * @param policy_provider_interface $policy
     * @param qualifying_factor_checker_interface $checker
     * @param rollout_configuration $configuration
     * @return authenticated_user_enforcer
     */
    private function create_ready_enforcer(
        policy_provider_interface $policy,
        qualifying_factor_checker_interface $checker,
        rollout_configuration $configuration,
    ): authenticated_user_enforcer {
        return new class ($policy, $checker, $configuration) extends authenticated_user_enforcer {
            /**
             * Makes Moodle MFA ready for focused decision tests.
             *
             * @return bool
             */
            protected function is_mfa_ready(): bool {
                return true;
            }
        };
    }
}

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
use local_forcemfa\local\core_route_exemption_provider;
use local_forcemfa\local\request_guard;
use local_forcemfa\local\return_url_manager;

/**
 * Tests for request routing decisions.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\request_guard
 */
final class request_guard_test extends \advanced_testcase {
    /**
     * Tests routes that must remain reachable while MFA setup is pending.
     *
     * @dataProvider safe_url_provider
     * @param string $path
     * @return void
     */
    public function test_safe_urls(string $path): void {
        $this->resetAfterTest();
        $this->assertTrue($this->create_guard()->is_safe_url(new \moodle_url($path)));
    }

    /**
     * Returns routes that must not be intercepted.
     *
     * @return array
     */
    public static function safe_url_provider(): array {
        return [
            'MFA preferences' => ['/admin/tool/mfa/user_preferences.php'],
            'MFA action' => ['/admin/tool/mfa/action.php'],
            'factor helper' => ['/admin/tool/mfa/factor/sms/editphonenumber.php'],
            'site policy' => ['/admin/tool/policy/view.php'],
            'registration confirmation' => ['/login/confirm.php'],
            'logout' => ['/login/logout.php'],
            'configuration support' => ['/local/forcemfa/configuration_error.php'],
        ];
    }

    /**
     * Tests normal content remains enforceable independently of course ownership.
     *
     * @return void
     */
    public function test_content_url_is_not_safe(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse($this->create_guard()->is_safe_url(new \moodle_url('/course/view.php', ['id' => 2])));
    }

    /**
     * Tests that tool_mfa administrator redirect exclusions do not bypass this policy.
     *
     * @return void
     */
    public function test_tool_mfa_redirect_exclusion_is_not_policy_exemption(): void {
        $this->resetAfterTest(true);
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('redir_exclusions', '/course/view.php', 'tool_mfa');

        $url = new \moodle_url('/course/view.php', ['id' => 2]);
        $this->assertFalse($this->create_guard()->is_safe_url($url));
    }

    /**
     * Tests that the MFA factor directory is not broadly exempted.
     *
     * @return void
     */
    public function test_arbitrary_factor_route_is_not_safe(): void {
        $this->resetAfterTest(true);
        $this->setUser($this->getDataGenerator()->create_user());
        $url = new \moodle_url('/admin/tool/mfa/factor/untrusted/arbitrary.php');
        $this->assertFalse($this->create_guard()->is_safe_url($url));
    }

    /**
     * Tests that a non-page request is denied rather than redirected.
     *
     * @return void
     */
    public function test_non_page_request_without_factor_throws_exception(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_config('enabled', 1, 'tool_mfa');
        set_config('enabled', 1, 'factor_nosetup');

        $enforcer = $this->createStub(authenticated_user_enforcer::class);
        $enforcer->method('evaluate')->willReturn(authenticated_user_enforcer::RESULT_SETUP_REQUIRED);
        $enforcer->method('enforce_non_page_result')->willThrowException(
            new \moodle_exception(
                'errorsetuprequired',
                'local_forcemfa',
                '',
                (new \moodle_url('/admin/tool/mfa/user_preferences.php'))->out(false),
            ),
        );

        $guard = new class (
            $enforcer,
            new return_url_manager(),
            new core_route_exemption_provider(),
        ) extends request_guard {
            /**
             * Marks the test request as AJAX or web service transport.
             *
             * @return bool
             */
            protected function is_non_page_request(): bool {
                return true;
            }
        };

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/^You must configure a multi-factor authentication method/');
        $guard->enforce();
    }

    /**
     * Tests that forced-password onboarding remains authoritative.
     *
     * @return void
     */
    public function test_forced_password_change_skips_enforcement(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('auth_forcepasswordchange', 1, $user);

        $enforcer = $this->createMock(authenticated_user_enforcer::class);
        $enforcer->expects($this->never())->method('evaluate');

        $guard = new request_guard(
            $enforcer,
            new return_url_manager(),
            new core_route_exemption_provider(),
        );
        $guard->enforce();
    }

    /**
     * Tests the site-administrator break-glass path for an unusable rollout.
     *
     * @return void
     */
    public function test_site_administrator_retains_repair_access_when_configuration_is_unusable(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $admin = $this->getDataGenerator()->create_user();
        $CFG->siteadmins .= ',' . $admin->id;
        $this->setUser($admin);

        $enforcer = $this->createStub(authenticated_user_enforcer::class);
        $enforcer->method('evaluate')->willReturn(authenticated_user_enforcer::RESULT_REPAIR_ALLOWED);

        $guard = new request_guard(
            $enforcer,
            new return_url_manager(),
            new core_route_exemption_provider(),
        );
        $guard->enforce();
        $this->assertTrue(is_siteadmin($admin));
    }

    /**
     * Tests that ordinary non-page requests fail safely under a broken rollout.
     *
     * @return void
     */
    public function test_unusable_configuration_blocks_ordinary_non_page_request(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $enforcer = $this->createStub(authenticated_user_enforcer::class);
        $enforcer->method('evaluate')->willReturn(authenticated_user_enforcer::RESULT_CONFIGURATION_UNAVAILABLE);
        $enforcer->method('enforce_non_page_result')->willThrowException(
            new \moodle_exception('errorconfigurationunavailable', 'local_forcemfa'),
        );

        $guard = new class (
            $enforcer,
            new return_url_manager(),
            new core_route_exemption_provider(),
        ) extends request_guard {
            /**
             * Marks the test request as AJAX or web service transport.
             *
             * @return bool
             */
            protected function is_non_page_request(): bool {
                return true;
            }
        };

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Multi-factor authentication setup is temporarily unavailable');
        $guard->enforce();
    }

    /**
     * Creates a guard for route-only tests.
     *
     * @return request_guard
     */
    private function create_guard(): request_guard {
        $enforcer = $this->createStub(authenticated_user_enforcer::class);
        $enforcer->method('evaluate')->willReturn(authenticated_user_enforcer::RESULT_NOT_ENFORCED);

        return new request_guard(
            $enforcer,
            new return_url_manager(),
            new core_route_exemption_provider(),
        );
    }
}

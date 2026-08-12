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

/**
 * Behat steps for local_forcemfa.
 *
 * @package    local_forcemfa
 * @category   test
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here; Behat may load this file before config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps for local_forcemfa.
 *
 * @package    local_forcemfa
 * @category   test
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_forcemfa extends behat_base {
    /**
     * Configures the supported first-factor rollout through tool_mfa's public action API.
     *
     * @Given /^a supported forced MFA test rollout is configured$/
     * @return void
     */
    public function configure_supported_rollout(): void {
        set_config('enabled', 1, 'tool_mfa');

        set_config('enabled', 1, 'factor_auth');
        set_config('weight', 100, 'factor_auth');
        set_config('goodauth', 'manual', 'factor_auth');

        set_config('enabled', 1, 'factor_nosetup');
        set_config('weight', 100, 'factor_nosetup');

        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 100, 'factor_totp');

        foreach (['auth', 'nosetup', 'totp'] as $factor) {
            \tool_mfa\manager::do_factor_action($factor, 'enable');
        }

        set_config('policy', 1, 'local_forcemfa');
    }

    /**
     * Verifies that both the site rollout and the current user are ready for tool_mfa.
     *
     * @Then /^the forced MFA rollout should be ready for the current user$/
     * @return void
     */
    public function assert_rollout_ready(): void {
        global $USER;

        $configuration = new \local_forcemfa\local\rollout_configuration(
            new \local_forcemfa\local\qualifying_factor_checker(),
        );
        $issues = $configuration->get_issues();

        if (!empty($issues)) {
            throw new Exception('Forced MFA rollout issues: ' . implode(', ', $issues));
        }

        if (!\tool_mfa\manager::is_ready()) {
            $hascapability = has_capability(
                'tool/mfa:mfaaccess',
                \context_user::instance($USER->id),
            );
            throw new Exception('tool_mfa is not ready; mfaaccess=' . (int) $hascapability);
        }
    }
}

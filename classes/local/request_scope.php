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

namespace local_forcemfa\local;

/**
 * Determines whether Moodle's existing authentication flow remains authoritative.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request_scope {
    /**
     * Returns whether an early browser request must not be enforced.
     *
     * @return bool
     */
    public static function should_skip_browser_request(): bool {
        global $CFG;

        if (
            (
                (defined('CLI_SCRIPT') && CLI_SCRIPT) ||
                (defined('NO_MOODLE_COOKIES') && NO_MOODLE_COOKIES)
            ) && !PHPUNIT_TEST
        ) {
            return true;
        }

        if (!empty($CFG->adminsetuppending) || !empty($CFG->upgraderunning)) {
            return true;
        }

        return self::should_skip_authenticated_user();
    }

    /**
     * Returns whether onboarding or account state must take precedence.
     *
     * Unlike should_skip_browser_request(), this method intentionally does not
     * exclude cookie-less requests because web-service authentication has already
     * established the current user when it is called.
     *
     * @return bool
     */
    public static function should_skip_authenticated_user(): bool {
        global $CFG, $SESSION, $USER;

        if (!isloggedin() || isguestuser() || \core\session\manager::is_loggedinas()) {
            return true;
        }

        if (
            empty($USER->id) || !empty($USER->deleted) || !empty($USER->suspended) ||
            (isset($USER->confirmed) && !$USER->confirmed)
        ) {
            return true;
        }

        if (user_not_fully_set_up($USER) || get_user_preferences('auth_forcepasswordchange', false, $USER)) {
            return true;
        }

        // Moodle MFA marks requests where a core onboarding route must take precedence.
        if (!empty($SESSION->mfa_pending)) {
            return true;
        }

        if (isset($USER->policyagreed) && !$USER->policyagreed) {
            $manager = new \core_privacy\local\sitepolicy\manager();
            if ($manager->get_redirect_url(false)) {
                return true;
            }
        }

        if (
            !empty($CFG->maintenance_enabled) &&
            !has_capability('moodle/site:maintenanceaccess', \context_system::instance())
        ) {
            return true;
        }

        return false;
    }
}

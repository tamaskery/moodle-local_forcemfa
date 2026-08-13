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
 * Library callbacks for local_forcemfa.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the forced MFA rollout configuration check to the security report.
 *
 * @return array
 */
function local_forcemfa_security_checks(): array {
    return [new \local_forcemfa\check\security\mfa_configuration()];
}

/**
 * Enforces configured-factor policy after Moodle authenticates a web-service user.
 *
 * Moodle invokes this callback after token/session authentication and parameter
 * validation, but before the external function. Returning false preserves normal
 * execution; a Moodle exception is serialized by the active web-service transport.
 *
 * @param stdClass $function External function metadata supplied by Moodle.
 * @param array $params Validated external function parameters.
 * @return bool Always false when execution may continue.
 */
function local_forcemfa_override_webservice_execution(stdClass $function, array $params): bool {
    global $USER;

    if (\local_forcemfa\local\request_scope::should_skip_authenticated_user()) {
        return false;
    }

    $enforcer = \local_forcemfa\local\authenticated_user_enforcer::create();
    $enforcer->enforce_non_page($USER);

    return false;
}

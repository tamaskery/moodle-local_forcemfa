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
 * Resolves the site-wide forced MFA setup policy.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_policy_provider implements policy_provider_interface {
    /** Forced setup is disabled. */
    public const POLICY_DISABLED = 0;

    /** Forced setup applies to everyone except actual Moodle site administrators. */
    public const POLICY_EXCEPT_SITE_ADMINS = 1;

    /** Forced setup applies to everyone, including Moodle site administrators. */
    public const POLICY_EVERYBODY = 2;

    /**
     * Returns whether the supplied user is covered by the global policy.
     *
     * @param \stdClass $user
     * @return bool
     */
    public function is_enforced(\stdClass $user): bool {
        $policy = (int) get_config('local_forcemfa', 'policy');

        if ($policy === self::POLICY_EVERYBODY) {
            return true;
        }

        if ($policy === self::POLICY_EXCEPT_SITE_ADMINS) {
            return !is_siteadmin($user->id);
        }

        return false;
    }
}

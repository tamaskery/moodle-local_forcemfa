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
     * Returns the validated global policy decision for the supplied user.
     *
     * @param \stdClass $user
     * @return policy_decision
     */
    public function get_decision(\stdClass $user): policy_decision {
        $policy = $this->get_configured_policy();

        if ($policy === null) {
            return policy_decision::invalid();
        }

        if ($policy === self::POLICY_EVERYBODY) {
            return policy_decision::valid(true);
        }

        if ($policy === self::POLICY_EXCEPT_SITE_ADMINS) {
            return policy_decision::valid(!is_siteadmin($user->id));
        }

        return policy_decision::valid(false);
    }

    /**
     * Returns the validated stored policy, or null for malformed configuration.
     *
     * A missing value is the documented disabled default. Moodle normally returns
     * stored configuration as a string, but accepting an integer keeps this method
     * deterministic in tests and during installation.
     *
     * @return int|null
     */
    public function get_configured_policy(): ?int {
        $value = get_config('local_forcemfa', 'policy');

        if ($value === false) {
            return self::POLICY_DISABLED;
        }

        if (is_int($value)) {
            $policy = $value;
        } else if (is_string($value) && preg_match('/^[0-2]$/D', $value)) {
            $policy = (int) $value;
        } else {
            return null;
        }

        if (!in_array($policy, [
            self::POLICY_DISABLED,
            self::POLICY_EXCEPT_SITE_ADMINS,
            self::POLICY_EVERYBODY,
        ], true)) {
            return null;
        }

        return $policy;
    }
}

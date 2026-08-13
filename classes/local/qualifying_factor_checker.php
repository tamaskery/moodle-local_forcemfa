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

use tool_mfa\plugininfo\factor;

/**
 * Current-user implementation of the qualifying MFA factor check.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qualifying_factor_checker implements qualifying_factor_checker_interface {
    /**
     * Returns whether an enrollable qualifying factor is available at site level.
     *
     * @return bool
     */
    public function has_available_factor(): bool {
        foreach (factor::get_enabled_factors() as $factor) {
            if ($this->factor_is_enrollable($factor)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the supplied current user can enroll a qualifying factor.
     *
     * The factor API can use current-session state while rendering setup controls,
     * so callers must pass the authenticated current user rather than another user.
     *
     * @param \stdClass $user
     * @return bool
     */
    public function can_setup_factor(\stdClass $user): bool {
        global $USER;

        if (empty($USER->id) || (int) $USER->id !== (int) $user->id) {
            return false;
        }

        foreach (factor::get_enabled_factors() as $factor) {
            if (!$this->factor_is_enrollable($factor)) {
                continue;
            }

            if (in_array(factor::STATE_PASS, $factor->possible_states($user), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the supplied user has an active qualifying factor.
     *
     * This method only asks each qualifying tool_mfa factor for instances belonging
     * to the supplied user. It never searches for or aggregates other users.
     *
     * @param \stdClass $user
     * @return bool
     */
    public function has_factor(\stdClass $user): bool {
        foreach (factor::get_enabled_factors() as $factor) {
            if (!$this->factor_qualifies($factor)) {
                continue;
            }

            if (!empty($factor->get_active_user_factors($user))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether a factor is a genuine user-setup factor for this policy.
     *
     * @param object $factor
     * @return bool
     */
    private function factor_qualifies(object $factor): bool {
        return $factor->has_setup() && $factor->get_weight() > 0;
    }

    /**
     * Returns whether a factor can actually be enrolled from Moodle's setup UI.
     *
     * @param object $factor
     * @return bool
     */
    private function factor_is_enrollable(object $factor): bool {
        return $this->factor_qualifies($factor) && $factor->show_setup_buttons();
    }
}

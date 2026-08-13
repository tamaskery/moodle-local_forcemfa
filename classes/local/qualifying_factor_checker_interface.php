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
 * Checks for enabled, positive-weight MFA factors that users configure themselves.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface qualifying_factor_checker_interface {
    /**
     * Returns whether an enrollable qualifying factor is available at site level.
     *
     * @return bool
     */
    public function has_available_factor(): bool;

    /**
     * Returns whether the supplied current user can enroll a qualifying factor.
     *
     * @param \stdClass $user
     * @return bool
     */
    public function can_setup_factor(\stdClass $user): bool;

    /**
     * Returns whether the supplied user has an active qualifying factor.
     *
     * @param \stdClass $user
     * @return bool
     */
    public function has_factor(\stdClass $user): bool;
}

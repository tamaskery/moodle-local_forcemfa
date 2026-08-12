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

namespace local_forcemfa\check\security;

use core\check\result;
use local_forcemfa\local\global_policy_provider;
use local_forcemfa\local\qualifying_factor_checker;
use local_forcemfa\local\rollout_configuration;

/**
 * Reports whether the supported first-factor rollout configuration is ready.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mfa_configuration extends \core\check\check {
    /**
     * Returns the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_name', 'local_forcemfa');
    }

    /**
     * Returns a link to the plugin settings.
     *
     * @return \action_link
     */
    public function get_action_link(): \action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'local_forcemfa']),
            get_string('check_action', 'local_forcemfa'),
        );
    }

    /**
     * Returns the configuration check result.
     *
     * @return result
     */
    public function get_result(): result {
        $details = get_string('check_details', 'local_forcemfa');
        $policy = (int) get_config('local_forcemfa', 'policy');

        if ($policy === global_policy_provider::POLICY_DISABLED) {
            return new result(result::NA, get_string('check_disabled', 'local_forcemfa'), $details);
        }

        $configuration = new rollout_configuration(new qualifying_factor_checker());
        $issues = $configuration->get_issues();
        if (empty($issues)) {
            return new result(result::OK, get_string('check_ok', 'local_forcemfa'), $details);
        }

        $items = [];
        foreach ($issues as $issue) {
            $items[] = \html_writer::tag('li', get_string('check_issue_' . $issue, 'local_forcemfa'));
        }
        $details .= \html_writer::tag('ul', implode('', $items));

        return new result(result::ERROR, get_string('check_problem', 'local_forcemfa'), $details);
    }
}

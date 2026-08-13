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

use core\hook\after_config;
use local_forcemfa\local\authenticated_user_enforcer;
use local_forcemfa\local\core_route_exemption_provider;
use local_forcemfa\local\request_guard;
use local_forcemfa\local\return_url_manager;

/**
 * Hook callbacks for local_forcemfa.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Enforces the current user's forced MFA setup policy after core configuration.
     *
     * @param after_config $hook
     * @return void
     */
    public static function after_config(after_config $hook): void {
        global $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            return;
        }

        // Hook discovery also occurs before this plugin has completed installation.
        if (!get_config('local_forcemfa', 'version')) {
            return;
        }

        $guard = new request_guard(
            authenticated_user_enforcer::create(),
            new return_url_manager(),
            new core_route_exemption_provider(),
        );
        $guard->enforce();
    }
}

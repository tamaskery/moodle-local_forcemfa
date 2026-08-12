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
 * Administrative settings for local_forcemfa.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_forcemfa\local\global_policy_provider;

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_forcemfa',
        get_string('pluginname', 'local_forcemfa'),
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configselect(
        'local_forcemfa/policy',
        get_string('policy', 'local_forcemfa'),
        get_string('policy_help', 'local_forcemfa'),
        global_policy_provider::POLICY_DISABLED,
        [
            global_policy_provider::POLICY_DISABLED => get_string('policy_disabled', 'local_forcemfa'),
            global_policy_provider::POLICY_EXCEPT_SITE_ADMINS =>
                get_string('policy_exceptsiteadmins', 'local_forcemfa'),
            global_policy_provider::POLICY_EVERYBODY => get_string('policy_everybody', 'local_forcemfa'),
        ],
    ));
}

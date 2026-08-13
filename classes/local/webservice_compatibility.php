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
 * Detects token transports that provide no post-authentication plugin hook.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_compatibility {
    /**
     * Returns whether another plugin can replace execution before this plugin runs.
     *
     * Moodle stops processing override callbacks after the first non-false result.
     * Treat any additional override as incompatible because plugin ordering and the
     * other callback's return behavior are outside this plugin's control.
     *
     * @return bool
     */
    public function has_competing_execution_override(): bool {
        $callbacks = get_plugins_with_function('override_webservice_execution');

        foreach ($callbacks as $plugins) {
            foreach ($plugins as $callback) {
                if ($callback !== 'local_forcemfa_override_webservice_execution') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns whether Moodle exposes an unhookable token file endpoint.
     *
     * Moodle's upload.php, pluginfile.php, and draftfile.php authenticate tokens
     * inside standalone scripts and do not invoke override_webservice_execution.
     * No users, tokens, or tenants are queried here; this inspects service settings.
     *
     * @return bool
     */
    public function has_unenforced_token_file_endpoints(): bool {
        global $CFG, $DB;

        // Tokenpluginfile.php uses long-lived core_files user keys and has no
        // post-authentication callback. Existing keys remain usable after factor revocation,
        // even when the external web-services advanced feature is later disabled.
        if ($DB->record_exists('user_private_key', ['script' => 'core_files'])) {
            return true;
        }

        if (empty($CFG->enablewebservices)) {
            return false;
        }

        return $DB->record_exists_select(
            'external_services',
            'enabled = :enabled AND (uploadfiles = :uploadfiles OR downloadfiles = :downloadfiles)',
            [
                'enabled' => 1,
                'uploadfiles' => 1,
                'downloadfiles' => 1,
            ],
        );
    }
}

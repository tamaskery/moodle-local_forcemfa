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
 * Core Moodle onboarding and MFA setup route exemptions.
 *
 * tool_mfa's administrator-defined redirect exclusions are deliberately not
 * imported because they are not exemptions from this plugin's prerequisite.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_route_exemption_provider implements route_exemption_provider_interface {
    /**
     * Returns whether the exact endpoint must remain reachable.
     *
     * @param \moodle_url $url
     * @return bool
     */
    public function is_exempt(\moodle_url $url): bool {
        $fixedurls = [
            new \moodle_url('/admin/tool/mfa/auth.php'),
            new \moodle_url('/admin/tool/mfa/user_preferences.php'),
            new \moodle_url('/admin/tool/mfa/action.php'),
            new \moodle_url('/admin/tool/mfa/guide.php'),
            new \moodle_url('/admin/tool/mfa/factor/sms/editphonenumber.php'),
            new \moodle_url('/admin/tool/policy/view.php'),
            new \moodle_url('/admin/tool/policy/index.php'),
            new \moodle_url('/login/confirm.php'),
            new \moodle_url('/login/logout.php'),
            new \moodle_url('/local/forcemfa/configuration_error.php'),
        ];

        foreach ($fixedurls as $fixedurl) {
            if ($this->same_endpoint($url, $fixedurl)) {
                return true;
            }
        }

        foreach (factor::get_factors() as $mfafactor) {
            foreach ($mfafactor->get_no_redirect_urls() as $factorurl) {
                if ($factorurl instanceof \moodle_url && $this->same_endpoint($url, $factorurl)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compares an exact script path while allowing endpoint-owned query parameters.
     *
     * @param \moodle_url $url
     * @param \moodle_url $candidate
     * @return bool
     */
    private function same_endpoint(\moodle_url $url, \moodle_url $candidate): bool {
        try {
            // Reject factor-declared external URLs before comparing route paths.
            $candidate->out_as_local_url(false);
        } catch (\coding_exception) {
            return false;
        }

        return $url->get_path() === $candidate->get_path();
    }
}

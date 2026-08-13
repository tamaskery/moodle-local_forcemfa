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

use core\output\notification;

/**
 * Applies the forced setup policy to the current authenticated request.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request_guard {
    /** @var authenticated_user_enforcer Current-user enforcement service. */
    private readonly authenticated_user_enforcer $enforcer;

    /** @var return_url_manager One-time return URL manager. */
    private readonly return_url_manager $returnurls;

    /** @var route_exemption_provider_interface Safe route provider. */
    private readonly route_exemption_provider_interface $routeexemptions;

    /**
     * Constructor.
     *
     * @param authenticated_user_enforcer $enforcer
     * @param return_url_manager $returnurls
     * @param route_exemption_provider_interface $routeexemptions
     */
    public function __construct(
        authenticated_user_enforcer $enforcer,
        return_url_manager $returnurls,
        route_exemption_provider_interface $routeexemptions,
    ) {
        $this->enforcer = $enforcer;
        $this->returnurls = $returnurls;
        $this->routeexemptions = $routeexemptions;
    }

    /**
     * Enforces the current request or lets Moodle continue normally.
     *
     * @return void
     */
    public function enforce(): void {
        global $USER;

        if (request_scope::should_skip_browser_request()) {
            return;
        }

        $currenturl = $this->get_current_url();
        $nonpagerequest = $this->is_non_page_request();
        $result = $this->enforcer->evaluate($USER);

        if ($result === authenticated_user_enforcer::RESULT_SATISFIED) {
            if (!$nonpagerequest && ($returnurl = $this->returnurls->take())) {
                redirect($returnurl);
            }
            return;
        }

        if (
            $result === authenticated_user_enforcer::RESULT_NOT_ENFORCED ||
            $result === authenticated_user_enforcer::RESULT_REPAIR_ALLOWED
        ) {
            return;
        }

        // Setup, challenge, logout, and authoritative onboarding endpoints must remain reachable.
        if ($this->is_safe_url($currenturl)) {
            return;
        }

        if ($result === authenticated_user_enforcer::RESULT_CONFIGURATION_UNAVAILABLE) {
            $this->returnurls->clear();

            if ($nonpagerequest) {
                $this->enforcer->enforce_non_page_result($result);
                return;
            }

            redirect(new \moodle_url('/local/forcemfa/configuration_error.php'));
        }

        $setupurl = new \moodle_url('/admin/tool/mfa/user_preferences.php');
        if ($nonpagerequest) {
            $this->enforcer->enforce_non_page_result($result);
            return;
        }

        $this->returnurls->store($currenturl);
        \core\notification::add(get_string('redirectmessage', 'local_forcemfa'), notification::NOTIFY_WARNING);
        redirect($setupurl);
    }

    /**
     * Returns whether a URL must remain accessible while setup is pending.
     *
     * Public for focused compatibility testing; callers should normally use enforce().
     *
     * @param \moodle_url $url
     * @return bool
     */
    public function is_safe_url(\moodle_url $url): bool {
        return $this->routeexemptions->is_exempt($url);
    }

    /**
     * Returns whether redirects are inappropriate for the request transport.
     *
     * @return bool
     */
    protected function is_non_page_request(): bool {
        return (defined('WS_SERVER') && WS_SERVER) || (defined('AJAX_SCRIPT') && AJAX_SCRIPT);
    }

    /**
     * Returns the best Moodle URL representation of the current request.
     *
     * @return \moodle_url
     */
    private function get_current_url(): \moodle_url {
        global $FULLME, $PAGE;

        if ($PAGE->has_set_url()) {
            return new \moodle_url($PAGE->url);
        }

        if (!empty($FULLME)) {
            return new \moodle_url($FULLME);
        }

        return new \moodle_url('/');
    }
}

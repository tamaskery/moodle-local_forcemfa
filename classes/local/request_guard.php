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
    /** @var policy_provider_interface Policy provider. */
    private readonly policy_provider_interface $policyprovider;

    /** @var qualifying_factor_checker_interface Qualifying factor checker. */
    private readonly qualifying_factor_checker_interface $factorchecker;

    /** @var rollout_configuration Rollout configuration validator. */
    private readonly rollout_configuration $configuration;

    /** @var return_url_manager One-time return URL manager. */
    private readonly return_url_manager $returnurls;

    /**
     * Constructor.
     *
     * @param policy_provider_interface $policyprovider
     * @param qualifying_factor_checker_interface $factorchecker
     * @param rollout_configuration $configuration
     * @param return_url_manager $returnurls
     */
    public function __construct(
        policy_provider_interface $policyprovider,
        qualifying_factor_checker_interface $factorchecker,
        rollout_configuration $configuration,
        return_url_manager $returnurls,
    ) {
        $this->policyprovider = $policyprovider;
        $this->factorchecker = $factorchecker;
        $this->configuration = $configuration;
        $this->returnurls = $returnurls;
    }

    /**
     * Enforces the current request or lets Moodle continue normally.
     *
     * @return void
     */
    public function enforce(): void {
        global $USER;

        if ($this->should_skip_request() || !$this->policyprovider->is_enforced($USER)) {
            return;
        }

        $currenturl = $this->get_current_url();
        $nonpagerequest = $this->is_non_page_request();

        if ($this->factorchecker->has_factor($USER)) {
            if (!$nonpagerequest && ($returnurl = $this->returnurls->take())) {
                redirect($returnurl);
            }
            return;
        }

        // Setup, challenge, logout, and authoritative onboarding endpoints must remain reachable.
        if ($this->is_safe_url($currenturl)) {
            return;
        }

        if (!$this->configuration->is_usable() || !\tool_mfa\manager::is_ready()) {
            $this->returnurls->clear();

            // This repair-only exception is deliberately independent from the configured policy.
            if (is_siteadmin($USER->id)) {
                return;
            }

            if ($nonpagerequest) {
                throw new \moodle_exception('errorconfigurationunavailable', 'local_forcemfa');
            }

            redirect(new \moodle_url('/local/forcemfa/configuration_error.php'));
        }

        $setupurl = new \moodle_url('/admin/tool/mfa/user_preferences.php');
        if ($nonpagerequest) {
            throw new \moodle_exception(
                'errorsetuprequired',
                'local_forcemfa',
                '',
                $setupurl->out(false),
            );
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
        $fixedurls = [
            new \moodle_url('/admin/tool/mfa/auth.php'),
            new \moodle_url('/admin/tool/mfa/user_preferences.php'),
            new \moodle_url('/admin/tool/mfa/action.php'),
            new \moodle_url('/admin/tool/mfa/guide.php'),
            new \moodle_url('/admin/tool/policy/view.php'),
            new \moodle_url('/admin/tool/policy/index.php'),
            new \moodle_url('/login/confirm.php'),
            new \moodle_url('/login/logout.php'),
            new \moodle_url('/local/forcemfa/configuration_error.php'),
        ];

        foreach ($fixedurls as $fixedurl) {
            if ($url->compare($fixedurl, URL_MATCH_BASE)) {
                return true;
            }
        }

        // Factor-owned setup helpers, such as the SMS phone editor, are part of tool_mfa's setup flow.
        $factorpath = (new \moodle_url('/admin/tool/mfa/factor/'))->get_path();
        if (str_starts_with($url->get_path(), $factorpath)) {
            return true;
        }

        foreach (\tool_mfa\manager::get_no_redirect_urls() as $noredirecturl) {
            if ($url->compare($noredirecturl)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the request is outside the applicable authenticated browser flow.
     *
     * @return bool
     */
    private function should_skip_request(): bool {
        global $CFG, $SESSION, $USER;

        if (
            (
                (defined('CLI_SCRIPT') && CLI_SCRIPT) ||
                (defined('NO_MOODLE_COOKIES') && NO_MOODLE_COOKIES)
            ) && !PHPUNIT_TEST
        ) {
            return true;
        }

        if (!empty($CFG->adminsetuppending) || !empty($CFG->upgraderunning)) {
            return true;
        }

        if (!isloggedin() || isguestuser() || \core\session\manager::is_loggedinas()) {
            return true;
        }

        if (
            empty($USER->id) || !empty($USER->deleted) || !empty($USER->suspended) ||
            (isset($USER->confirmed) && !$USER->confirmed)
        ) {
            return true;
        }

        if (user_not_fully_set_up($USER) || get_user_preferences('auth_forcepasswordchange', false, $USER)) {
            return true;
        }

        // Moodle MFA marks requests where a core onboarding route must take precedence.
        if (!empty($SESSION->mfa_pending)) {
            return true;
        }

        if (isset($USER->policyagreed) && !$USER->policyagreed) {
            $manager = new \core_privacy\local\sitepolicy\manager();
            if ($manager->get_redirect_url(false)) {
                return true;
            }
        }

        if (
            !empty($CFG->maintenance_enabled) &&
            !has_capability('moodle/site:maintenanceaccess', \context_system::instance())
        ) {
            return true;
        }

        return false;
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

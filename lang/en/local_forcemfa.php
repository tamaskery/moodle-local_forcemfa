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
 * Language strings for local_forcemfa.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['check_action'] = 'Review forced MFA settings';
$string['check_details'] = 'Forced MFA setup requires Moodle MFA, a 100-point no-setup rollout factor, '
    . 'and at least one enabled positive-weight factor that users can set up.';
$string['check_disabled'] = 'Forced MFA setup is disabled.';
$string['check_issue_noqualifyingfactor'] = 'No enabled positive-weight user-setup MFA factor is available.';
$string['check_issue_nosetupdisabled'] = 'The no-setup MFA rollout factor is not enabled.';
$string['check_issue_nosetupweight'] = 'The no-setup MFA rollout factor has a weight below 100.';
$string['check_issue_toolmfadisabled'] = 'Moodle multi-factor authentication is disabled.';
$string['check_name'] = 'Forced MFA setup configuration';
$string['check_ok'] = 'Forced MFA setup has the required rollout configuration.';
$string['check_problem'] = 'Forced MFA setup is enabled but its rollout configuration is incomplete.';
$string['configurationerror'] = 'MFA setup is temporarily unavailable';
$string['configurationerror_message'] = 'Your account requires multi-factor authentication, but setup is not currently '
    . 'available. Contact your site support team.';
$string['errorconfigurationunavailable'] = 'Multi-factor authentication setup is temporarily unavailable. '
    . 'Contact your site support team.';
$string['errorsetuprequired'] = 'You must configure a multi-factor authentication method before using this service. '
    . 'Open {$a} in a web browser to continue.';
$string['pluginname'] = 'Force MFA setup';
$string['policy'] = 'Force MFA setup';
$string['policy_disabled'] = 'Disabled';
$string['policy_everybody'] = 'Enabled for everybody';
$string['policy_exceptsiteadmins'] = 'Enabled except site administrators';
$string['policy_help'] = 'Requires each covered user to configure an enabled, positive-weight MFA factor that supports '
    . 'user setup. Moodle site administrators are identified only by Moodle\'s site administrator list; Workplace tenant '
    . 'administrators are ordinary covered users.';
$string['privacy:metadata'] = 'The Force MFA setup plugin does not store personal data. It keeps a temporary site-local '
    . 'return path in the current user session while setup is pending.';
$string['redirectmessage'] = 'Before continuing, configure a multi-factor authentication method for your account.';
$string['retry'] = 'Try again';

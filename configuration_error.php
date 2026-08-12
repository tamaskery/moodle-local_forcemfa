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
 * Safe error page shown when forced MFA setup is not usable.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login(null, false);

$url = new moodle_url('/local/forcemfa/configuration_error.php');
$context = context_system::instance();
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('configurationerror', 'local_forcemfa'));
$PAGE->set_heading(get_string('configurationerror', 'local_forcemfa'));
$PAGE->set_cacheable(false);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configurationerror', 'local_forcemfa'));
echo $OUTPUT->notification(
    get_string('configurationerror_message', 'local_forcemfa'),
    \core\output\notification::NOTIFY_ERROR,
);
echo $OUTPUT->single_button(new moodle_url('/'), get_string('retry', 'local_forcemfa'));
echo $OUTPUT->single_button(new moodle_url('/login/logout.php', ['sesskey' => sesskey()]), get_string('logout'));
echo $OUTPUT->footer();

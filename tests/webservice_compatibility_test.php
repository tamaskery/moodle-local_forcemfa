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

use local_forcemfa\local\webservice_compatibility;

/**
 * Tests detection of unhookable direct token file endpoints.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\webservice_compatibility
 */
final class webservice_compatibility_test extends \advanced_testcase {
    /**
     * Tests service-level upload and download detection.
     *
     * @return void
     */
    public function test_enabled_token_file_service_is_reported(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $CFG->enablewebservices = true;
        $DB->set_field_select('external_services', 'enabled', 0, '1 = 1');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_webservice');
        $service = $generator->create_service([
            'name' => 'Forced MFA compatibility test',
            'shortname' => 'local_forcemfa_compatibility_test',
            'enabled' => true,
        ]);

        $compatibility = new webservice_compatibility();
        $this->assertFalse($compatibility->has_unenforced_token_file_endpoints());

        $DB->set_field('external_services', 'downloadfiles', 1, ['id' => $service->id]);
        $this->assertTrue($compatibility->has_unenforced_token_file_endpoints());

        $DB->set_field('external_services', 'enabled', 0, ['id' => $service->id]);
        $this->assertFalse($compatibility->has_unenforced_token_file_endpoints());
    }

    /**
     * Tests detection of long-lived tokenpluginfile user keys.
     *
     * @return void
     */
    public function test_core_files_user_key_is_reported(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $CFG->enablewebservices = false;
        $user = $this->getDataGenerator()->create_user();
        get_user_key('core_files', $user->id);

        $compatibility = new webservice_compatibility();
        $this->assertTrue($compatibility->has_unenforced_token_file_endpoints());
    }
}

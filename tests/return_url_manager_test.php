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

use local_forcemfa\local\return_url_manager;

/**
 * Tests for one-time local return URL handling.
 *
 * @package    local_forcemfa
 * @copyright  2026 Tamas Kery
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forcemfa\local\return_url_manager
 */
final class return_url_manager_test extends \advanced_testcase {
    /**
     * Tests root-relative storage and atomic one-time retrieval.
     *
     * @return void
     */
    public function test_store_and_take_once(): void {
        $this->resetAfterTest();
        $manager = new return_url_manager();

        $this->assertTrue($manager->store(new \moodle_url('/course/view.php', ['id' => 7])));
        $returnurl = $manager->take();
        $this->assertNotNull($returnurl);
        $this->assertSame('/course/view.php?id=7', $returnurl->out_as_local_url(false));
        $this->assertNull($manager->take());
    }

    /**
     * Tests rejection of a URL from another origin.
     *
     * @return void
     */
    public function test_external_origin_is_rejected(): void {
        $this->resetAfterTest();
        $manager = new return_url_manager();

        $this->assertFalse($manager->store(new \moodle_url('https://attacker.example/steal')));
        $this->assertNull($manager->take());
    }

    /**
     * Tests that untrusted or malformed session state is discarded.
     *
     * @dataProvider invalid_session_url_provider
     * @param mixed $value
     * @return void
     */
    public function test_invalid_session_value_is_discarded(mixed $value): void {
        global $SESSION;

        $this->resetAfterTest();
        $SESSION->local_forcemfa_returnurl = $value;
        $manager = new return_url_manager();

        $this->assertNull($manager->take());
        $this->assertFalse(isset($SESSION->local_forcemfa_returnurl));
    }

    /**
     * Returns invalid session return URL values.
     *
     * @return array
     */
    public static function invalid_session_url_provider(): array {
        return [
            'absolute URL' => ['https://attacker.example/steal'],
            'scheme-relative URL' => ['//attacker.example/steal'],
            'backslash ambiguity' => ['/\\attacker.example/steal'],
            'non-string value' => [42],
        ];
    }
}
